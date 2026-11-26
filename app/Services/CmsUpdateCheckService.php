<?php

namespace App\Services;

use App\Support\CmsVersion;
use App\Support\SafeReleaseArchiveExtractor;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CmsUpdateCheckService
{
    public const int ERROR_BACKOFF_SECONDS = 3600;

    public function __construct(
        private readonly ?string $versionFilePath = null,
    ) {}

    /**
     * @return array{
     *     channel: string,
     *     installed_version: string,
     *     installed_commit: ?string,
     *     latest_version: ?string,
     *     latest_tag: ?string,
     *     latest_commit: ?string,
     *     update_available: bool,
     *     published_at: ?string,
     *     release_url: ?string,
     *     release_notes: ?string,
     *     zip_url: ?string,
     *     sha256_url: ?string,
     *     checked_at: ?int,
     *     retry_after: ?int,
     *     last_error: ?string
     * }
     */
    public function getCachedState(): array
    {
        $installed = CmsVersion::fromFile($this->versionFilePath);
        $channel = $this->activeChannel();

        $defaults = [
            'channel' => $channel,
            'installed_version' => $installed['version'],
            'installed_commit' => $installed['commit'],
            'latest_version' => null,
            'latest_tag' => null,
            'latest_commit' => null,
            'update_available' => false,
            'published_at' => null,
            'release_url' => null,
            'release_notes' => null,
            'zip_url' => null,
            'sha256_url' => null,
            'checked_at' => null,
            'retry_after' => null,
            'last_error' => null,
        ];

        $statePath = $this->stateFilePath();
        if (! is_file($statePath) || ! is_readable($statePath)) {
            return $defaults;
        }

        $raw = @file_get_contents($statePath);
        if (! is_string($raw) || trim($raw) === '') {
            return $defaults;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return $defaults;
        }

        $merged = array_merge($defaults, array_intersect_key($decoded, $defaults));
        $merged['installed_version'] = $installed['version'];
        $merged['installed_commit'] = $installed['commit'];

        if (($merged['channel'] ?? $channel) === 'stable' && is_string($merged['latest_version']) && $merged['latest_version'] !== '') {
            $merged['update_available'] = version_compare($merged['latest_version'], $installed['version'], '>');
        } elseif (($merged['channel'] ?? $channel) === 'dev' && is_string($merged['latest_commit']) && $merged['latest_commit'] !== '') {
            $merged['update_available'] = $installed['commit'] === null
                || ! hash_equals(strtolower($installed['commit']), strtolower($merged['latest_commit']));
        }

        return $merged;
    }

    /**
     * @return array<string, mixed>
     */
    public function checkIfStale(): array
    {
        $cached = $this->getCachedState();
        if (! (bool) config('satker.update.enabled', true)) {
            return $cached;
        }

        if (! $this->isStale($cached)) {
            return $cached;
        }

        $lock = Cache::lock('cms-update-check', 15);
        if (! $lock->get()) {
            return $cached;
        }

        try {
            return $this->check(force: false);
        } finally {
            $lock->release();
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function check(bool $force = false): array
    {
        $cached = $this->getCachedState();
        if (! (bool) config('satker.update.enabled', true) && ! $force) {
            return $cached;
        }

        if (! $force && ! $this->isStale($cached)) {
            return $cached;
        }

        $channel = $this->activeChannel();
        $repository = $this->validatedRepository();
        $installed = CmsVersion::fromFile($this->versionFilePath);

        try {
            $state = $channel === 'dev'
                ? $this->fetchBranchState($repository, $installed)
                : $this->fetchLatestReleaseState($repository, $installed);

            $this->persistState($state);

            return $state;
        } catch (Throwable $e) {
            Log::warning('Pemeriksaan pembaruan GitHub gagal: '.$e->getMessage());

            $failedState = array_merge($cached, [
                'channel' => $channel,
                'installed_version' => $installed['version'],
                'installed_commit' => $installed['commit'],
                'checked_at' => now()->timestamp,
                'retry_after' => now()->addSeconds(self::ERROR_BACKOFF_SECONDS)->timestamp,
                'last_error' => $this->friendlyErrorMessage($e->getMessage()),
            ]);

            $this->persistState($failedState);

            return $failedState;
        }
    }

    /**
     * @return array{
     *     version: string,
     *     tag: string,
     *     published_at: ?string,
     *     release_url: ?string,
     *     release_notes: ?string,
     *     zip_url: ?string,
     *     sha256_url: ?string
     * }
     */
    public function fetchReleaseByTag(?string $tag = null): array
    {
        $repository = $this->validatedRepository();
        $endpoint = $tag !== null && trim($tag) !== ''
            ? "https://api.github.com/repos/{$repository}/releases/tags/".rawurlencode($this->normalizeTagInput($tag))
            : "https://api.github.com/repos/{$repository}/releases/latest";

        $response = $this->githubApiClient()->get($endpoint);
        if (! $response->successful()) {
            throw new RuntimeException("GitHub API mengembalikan status HTTP {$response->status()}.");
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException('Respons GitHub Releases tidak berformat JSON valid.');
        }

        if (($payload['draft'] ?? false) === true) {
            throw new RuntimeException('Rilis yang diminta masih berstatus draf.');
        }

        $rawTag = trim((string) ($payload['tag_name'] ?? ''));
        if ($rawTag === '') {
            throw new RuntimeException('Rilis GitHub tidak memiliki atribut tag_name.');
        }

        [$zipUrl, $sha256Url] = $this->extractReleaseAssetUrls($payload['assets'] ?? []);

        return [
            'version' => CmsVersion::normalize($rawTag),
            'tag' => $rawTag,
            'published_at' => isset($payload['published_at']) && is_string($payload['published_at']) ? $payload['published_at'] : null,
            'release_url' => isset($payload['html_url']) && is_string($payload['html_url']) ? $payload['html_url'] : null,
            'release_notes' => $this->summarizeReleaseNotes((string) ($payload['body'] ?? '')),
            'zip_url' => $zipUrl,
            'sha256_url' => $sha256Url,
        ];
    }

    public function markInstalled(string $version, ?string $commit = null): void
    {
        $normalizedVersion = CmsVersion::normalize($version);
        $current = $this->getCachedState();

        $updated = array_merge($current, [
            'installed_version' => $normalizedVersion,
            'installed_commit' => $commit ?? $current['installed_commit'],
            'update_available' => false,
            'last_error' => null,
            'retry_after' => null,
            'checked_at' => now()->timestamp,
        ]);

        $this->persistState($updated);
    }

    /**
     * @param  array<string, mixed>  $cached
     */
    private function isStale(array $cached): bool
    {
        $now = now()->timestamp;
        $retryAfter = isset($cached['retry_after']) ? (int) $cached['retry_after'] : 0;
        if ($retryAfter > $now) {
            return false;
        }

        if (($cached['channel'] ?? '') !== $this->activeChannel()) {
            return true;
        }

        $checkedAt = isset($cached['checked_at']) ? (int) $cached['checked_at'] : 0;
        if ($checkedAt <= 0) {
            return true;
        }

        $ttl = max(300, (int) config('satker.update.check_ttl_seconds', 43200));

        return ($now - $checkedAt) >= $ttl;
    }

    /**
     * @param  array{version: string, commit: ?string, released_at: ?string, min_php: string}  $installed
     * @return array<string, mixed>
     */
    private function fetchLatestReleaseState(string $repository, array $installed): array
    {
        $response = $this->githubApiClient()->get("https://api.github.com/repos/{$repository}/releases/latest");
        if (! $response->successful()) {
            throw new RuntimeException("GitHub Releases API gagal dengan status HTTP {$response->status()}.");
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException('Respons GitHub Releases tidak valid.');
        }

        $isDraftOrPre = ($payload['draft'] ?? false) === true || ($payload['prerelease'] ?? false) === true;
        $rawTag = trim((string) ($payload['tag_name'] ?? ''));

        if ($isDraftOrPre || $rawTag === '') {
            return [
                'channel' => 'stable',
                'installed_version' => $installed['version'],
                'installed_commit' => $installed['commit'],
                'latest_version' => $installed['version'],
                'latest_tag' => null,
                'latest_commit' => null,
                'update_available' => false,
                'published_at' => null,
                'release_url' => null,
                'release_notes' => null,
                'zip_url' => null,
                'sha256_url' => null,
                'checked_at' => now()->timestamp,
                'retry_after' => null,
                'last_error' => null,
            ];
        }

        $latestVersion = CmsVersion::normalize($rawTag);
        [$zipUrl, $sha256Url] = $this->extractReleaseAssetUrls($payload['assets'] ?? []);

        return [
            'channel' => 'stable',
            'installed_version' => $installed['version'],
            'installed_commit' => $installed['commit'],
            'latest_version' => $latestVersion,
            'latest_tag' => $rawTag,
            'latest_commit' => null,
            'update_available' => version_compare($latestVersion, $installed['version'], '>'),
            'published_at' => isset($payload['published_at']) && is_string($payload['published_at']) ? $payload['published_at'] : null,
            'release_url' => isset($payload['html_url']) && is_string($payload['html_url']) ? $payload['html_url'] : null,
            'release_notes' => $this->summarizeReleaseNotes((string) ($payload['body'] ?? '')),
            'zip_url' => $zipUrl,
            'sha256_url' => $sha256Url,
            'checked_at' => now()->timestamp,
            'retry_after' => null,
            'last_error' => null,
        ];
    }

    /**
     * @param  array{version: string, commit: ?string, released_at: ?string, min_php: string}  $installed
     * @return array<string, mixed>
     */
    private function fetchBranchState(string $repository, array $installed): array
    {
        $branch = trim((string) config('satker.update.branch', 'main')) ?: 'main';
        $response = $this->githubApiClient()->get("https://api.github.com/repos/{$repository}/commits/".rawurlencode($branch));

        if (! $response->successful()) {
            throw new RuntimeException("GitHub Commits API gagal dengan status HTTP {$response->status()}.");
        }

        $payload = $response->json();
        $latestSha = is_array($payload) ? strtolower(trim((string) ($payload['sha'] ?? ''))) : '';
        if (preg_match('/^[a-f0-9]{40}$/', $latestSha) !== 1) {
            throw new RuntimeException('Respons SHA commit GitHub tidak valid.');
        }

        $updateAvailable = $installed['commit'] === null
            || ! hash_equals(strtolower($installed['commit']), $latestSha);

        $commitMessage = is_array($payload['commit'] ?? null)
            ? (string) ($payload['commit']['message'] ?? '')
            : '';
        $commitDate = is_array($payload['commit']['committer'] ?? null)
            ? ($payload['commit']['committer']['date'] ?? null)
            : null;

        return [
            'channel' => 'dev',
            'installed_version' => $installed['version'],
            'installed_commit' => $installed['commit'],
            'latest_version' => $branch.'@'.substr($latestSha, 0, 7),
            'latest_tag' => null,
            'latest_commit' => $latestSha,
            'update_available' => $updateAvailable,
            'published_at' => is_string($commitDate) ? $commitDate : null,
            'release_url' => isset($payload['html_url']) && is_string($payload['html_url']) ? $payload['html_url'] : null,
            'release_notes' => $this->summarizeReleaseNotes($commitMessage),
            'zip_url' => null,
            'sha256_url' => null,
            'checked_at' => now()->timestamp,
            'retry_after' => null,
            'last_error' => null,
        ];
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function extractReleaseAssetUrls(mixed $assets): array
    {
        if (! is_array($assets)) {
            return [null, null];
        }

        $zipUrl = null;
        $sha256Url = null;

        foreach ($assets as $asset) {
            if (! is_array($asset)) {
                continue;
            }

            $name = strtolower(trim((string) ($asset['name'] ?? '')));
            $downloadUrl = trim((string) ($asset['browser_download_url'] ?? ''));

            if ($name === '' || $downloadUrl === '' || ! SafeReleaseArchiveExtractor::isAllowedDownloadUrl($downloadUrl)) {
                continue;
            }

            if ($sha256Url === null && str_ends_with($name, '.zip.sha256')) {
                $sha256Url = $downloadUrl;

                continue;
            }

            if ($zipUrl === null && str_ends_with($name, '.zip')) {
                $zipUrl = $downloadUrl;
            }
        }

        return [$zipUrl, $sha256Url];
    }

    private function githubApiClient(): PendingRequest
    {
        $request = Http::connectTimeout(3)
            ->timeout(5)
            ->withHeaders([
                'Accept' => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
                'User-Agent' => 'SatkerCMS-Updater',
            ]);

        $token = trim((string) config('satker.update.github_token', ''));
        if ($token !== '') {
            $request = $request->withToken($token);
        }

        return $request;
    }

    private function activeChannel(): string
    {
        $channel = strtolower(trim((string) config('satker.update.channel', 'stable')));

        return $channel === 'dev' ? 'dev' : 'stable';
    }

    private function validatedRepository(): string
    {
        $repo = trim((string) config('satker.update.repository', 'rtriadi/cms-laravel-template-badilag'));
        if (preg_match('/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $repo) !== 1) {
            throw new RuntimeException('Format repositori GitHub pada CMS_UPDATE_REPO tidak valid.');
        }

        return $repo;
    }

    private function normalizeTagInput(string $tag): string
    {
        $trimmed = trim($tag);
        if (preg_match('/^\d/', $trimmed) === 1) {
            return 'v'.$trimmed;
        }

        return $trimmed;
    }

    private function summarizeReleaseNotes(string $rawBody): ?string
    {
        $cleaned = strip_tags($rawBody);
        $cleaned = str_replace(["\r\n", "\r", "\u{2014}", "\u{2013}"], ["\n", "\n", ':', '-'], $cleaned);
        $lines = array_values(array_filter(array_map(static function (string $line): string {
            $trimmed = trim(preg_replace('/^#{1,6}\s*/', '', $line) ?? '');

            return $trimmed;
        }, explode("\n", $cleaned))));

        if ($lines === []) {
            return null;
        }

        return Str::limit(implode("\n", array_slice($lines, 0, 6)), 550);
    }

    private function friendlyErrorMessage(string $rawError): string
    {
        if (str_contains($rawError, '403') || str_contains($rawError, '429')) {
            return 'Batas kuota permintaan GitHub API tercapai sementara. Sistem akan mencoba kembali otomatis dalam 1 jam.';
        }

        if (str_contains($rawError, '404')) {
            return 'Belum ada rilis publik yang ditemukan pada repositori GitHub.';
        }

        return 'Tidak dapat menghubungi GitHub saat ini. Periksa koneksi internet peladen atau coba kembali nanti.';
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function persistState(array $state): void
    {
        $path = $this->stateFilePath();
        $dir = dirname($path);

        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            return;
        }

        @file_put_contents($path, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    private function stateFilePath(): string
    {
        $configured = trim((string) config('satker.update.state_path', ''));

        return $configured !== '' ? $configured : storage_path('app/cms-update-state.json');
    }
}
