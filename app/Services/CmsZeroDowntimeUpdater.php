<?php

namespace App\Services;

use App\Support\CmsVersion;
use App\Support\SafeReleaseArchiveExtractor;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Activitylog\ActivityLogger;
use Throwable;

class CmsZeroDowntimeUpdater
{
    private const int MAX_REDIRECT_HOPS = 5;

    /**
     * @var array<int, string>
     */
    private const array CORE_SWAP_TARGETS = [
        'vendor',
        'app',
        'bootstrap/app.php',
        'bootstrap/providers.php',
        'config',
        'database/migrations',
        'database/seeders',
        'database/factories',
        'database/data',
        'lang',
        'resources',
        'routes',
        'artisan',
        'composer.json',
        'composer.lock',
        'version.json',
    ];

    /**
     * @param  ?Closure(string): bool  $postCodeSwapRunner
     */
    public function __construct(
        private readonly SafeReleaseArchiveExtractor $extractor,
        private readonly CmsUpdateCheckService $updateChecker,
        private readonly ?Closure $postCodeSwapRunner = null,
    ) {}

    /**
     * @return array{
     *     mode: string,
     *     base_path: string,
     *     root_path: string,
     *     public_path: string,
     *     shared_path: ?string,
     *     current_link: ?string,
     *     releases_path: ?string
     * }
     */
    public function detectLayout(?string $basePath = null): array
    {
        $resolvedBase = rtrim($basePath ?? base_path(), '/\\');
        $parentDir = dirname($resolvedBase);

        if (basename($parentDir) === 'releases') {
            $rootCandidate = dirname($parentDir);
            $sharedCandidate = $rootCandidate.DIRECTORY_SEPARATOR.'shared';
            $currentCandidate = $rootCandidate.DIRECTORY_SEPARATOR.'current';

            if (is_dir($sharedCandidate) && $this->isDirectoryLink($currentCandidate)) {
                return [
                    'mode' => 'atomic_release',
                    'base_path' => $resolvedBase,
                    'root_path' => $rootCandidate,
                    'public_path' => $resolvedBase.DIRECTORY_SEPARATOR.'public',
                    'shared_path' => $sharedCandidate,
                    'current_link' => $currentCandidate,
                    'releases_path' => $parentDir,
                ];
            }
        }

        return [
            'mode' => 'staged_inplace',
            'base_path' => $resolvedBase,
            'root_path' => $resolvedBase,
            'public_path' => $this->resolveStagedPublicPath($resolvedBase, $basePath === null),
            'shared_path' => null,
            'current_link' => null,
            'releases_path' => null,
        ];
    }

    public function downloadSecurely(string $url, string $destinationPath): void
    {
        $currentUrl = trim($url);
        if (! SafeReleaseArchiveExtractor::isAllowedDownloadUrl($currentUrl)) {
            throw new RuntimeException('URL unduhan rilis ditolak karena berada di luar daftar host resmi GitHub HTTPS.');
        }

        $token = trim((string) config('satker.update.github_token', ''));

        for ($hop = 0; $hop <= self::MAX_REDIRECT_HOPS; $hop++) {
            $host = strtolower((string) parse_url($currentUrl, PHP_URL_HOST));
            $headers = [
                'User-Agent' => 'SatkerCMS-Updater',
            ];

            $request = Http::connectTimeout(5)
                ->timeout(120)
                ->withoutRedirecting();

            if ($host === 'api.github.com') {
                $headers['Accept'] = 'application/octet-stream';
                if ($token !== '') {
                    $request = $request->withToken($token);
                }
            }

            $response = $request->withHeaders($headers)->get($currentUrl);
            $status = $response->status();

            if (in_array($status, [301, 302, 303, 307, 308], true)) {
                $location = trim((string) $response->header('Location'));
                if ($location === '' || ! SafeReleaseArchiveExtractor::isAllowedDownloadUrl($location)) {
                    throw new RuntimeException('Pengalihan URL unduhan ditolak karena mengarah ke host tidak terpercaya.');
                }

                $currentUrl = $location;

                continue;
            }

            if (! $response->successful()) {
                throw new RuntimeException("Gagal mengunduh berkas rilis dari GitHub (HTTP {$status}).");
            }

            $parentDir = dirname($destinationPath);
            if (! is_dir($parentDir) && ! mkdir($parentDir, 0775, true) && ! is_dir($parentDir)) {
                throw new RuntimeException('Gagal menyiapkan direktori sementara unduhan rilis.');
            }

            if (@file_put_contents($destinationPath, $response->body()) === false) {
                throw new RuntimeException('Gagal menyimpan berkas rilis ke ruang penyimpanan sementara.');
            }

            return;
        }

        throw new RuntimeException('Unduhan rilis dihentikan karena melebihi batas pengalihan URL.');
    }

    /**
     * @param  array{version: string, tag?: string, zip_url: ?string, sha256_url: ?string}  $release
     * @return array{mode: string, version: string, previous_version: string}
     */
    public function updateToRelease(array $release, ?string $basePath = null): array
    {
        $this->preloadRuntimeClasses();

        if (! extension_loaded('zip')) {
            throw new RuntimeException('Ekstensi PHP zip wajib aktif untuk mengekstrak paket pembaruan.');
        }

        $zipUrl = trim((string) ($release['zip_url'] ?? ''));
        $sha256Url = trim((string) ($release['sha256_url'] ?? ''));

        if ($zipUrl === '' || $sha256Url === '') {
            throw new RuntimeException('Rilis GitHub belum melampirkan paket satkercms-*.zip beserta berkas .sha256.');
        }

        if (! SafeReleaseArchiveExtractor::isAllowedDownloadUrl($zipUrl) || ! SafeReleaseArchiveExtractor::isAllowedDownloadUrl($sha256Url)) {
            throw new RuntimeException('URL artefak rilis tidak lolos pemeriksaan keamanan host GitHub.');
        }

        $layout = $this->detectLayout($basePath);
        $this->assertLayoutWritable($layout);

        return $this->withExclusiveLock($layout, function () use ($release, $zipUrl, $sha256Url, $layout): array {
            $updatesRoot = $this->updatesWorkspacePath($layout);
            $jobDir = $updatesRoot.DIRECTORY_SEPARATOR.'job-'.bin2hex(random_bytes(6));
            $zipPath = $jobDir.DIRECTORY_SEPARATOR.'release.zip';
            $shaPath = $jobDir.DIRECTORY_SEPARATOR.'release.zip.sha256';
            $extractDir = $jobDir.DIRECTORY_SEPARATOR.'extracted';

            try {
                $this->downloadSecurely($sha256Url, $shaPath);
                $this->downloadSecurely($zipUrl, $zipPath);

                $expectedSha = (string) @file_get_contents($shaPath);
                if (! SafeReleaseArchiveExtractor::verifySha256($zipPath, $expectedSha)) {
                    throw new RuntimeException('Verifikasi integritas SHA-256 gagal. Paket pembaruan ditolak.');
                }

                $this->extractor->extract($zipPath, $extractDir);

                $newMeta = CmsVersion::fromFile($extractDir.DIRECTORY_SEPARATOR.'version.json');
                if (version_compare(PHP_VERSION, $newMeta['min_php'], '<')) {
                    throw new RuntimeException("Rilis {$newMeta['version']} membutuhkan minimal PHP {$newMeta['min_php']} (saat ini ".PHP_VERSION.').');
                }

                $previousVersion = CmsVersion::current($layout['base_path'].DIRECTORY_SEPARATOR.'version.json');

                if ($layout['mode'] === 'atomic_release') {
                    $this->applyAtomicRelease($layout, $extractDir, $release, $newMeta['version']);
                } else {
                    $this->applyStagedInplace($layout, $extractDir, $previousVersion);
                }

                $this->triggerWebOpcacheReset();
                $this->updateChecker->markInstalled($newMeta['version'], $newMeta['commit']);
                $this->logSystemActivity('Memperbarui SatkerCMS secara zero-downtime', [
                    'mode' => $layout['mode'],
                    'from_version' => $previousVersion,
                    'to_version' => $newMeta['version'],
                ]);

                return [
                    'mode' => $layout['mode'],
                    'version' => $newMeta['version'],
                    'previous_version' => $previousVersion,
                ];
            } finally {
                $this->removePath($jobDir);
            }
        });
    }

    /**
     * @return array{mode: string, version: string}
     */
    public function rollback(?string $targetRelease = null, ?string $basePath = null): array
    {
        $this->preloadRuntimeClasses();
        $layout = $this->detectLayout($basePath);
        $this->assertLayoutWritable($layout);

        return $this->withExclusiveLock($layout, function () use ($layout, $targetRelease): array {
            $rolledVersion = $layout['mode'] === 'atomic_release'
                ? $this->rollbackAtomicRelease($layout, $targetRelease)
                : $this->rollbackStagedInplace($layout, $targetRelease);

            $this->triggerWebOpcacheReset();
            $this->updateChecker->markInstalled($rolledVersion);
            $this->logSystemActivity('Melakukan rollback versi SatkerCMS', [
                'mode' => $layout['mode'],
                'restored_version' => $rolledVersion,
            ]);

            return [
                'mode' => $layout['mode'],
                'version' => $rolledVersion,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $layout
     * @param  array<string, mixed>  $release
     */
    private function applyAtomicRelease(array $layout, string $extractDir, array $release, string $newVersion): void
    {
        $releasesPath = (string) $layout['releases_path'];
        $sharedPath = (string) $layout['shared_path'];
        $currentLink = (string) $layout['current_link'];

        $rawFolder = trim((string) ($release['tag'] ?? 'v'.$newVersion));
        $folderName = preg_replace('/[^A-Za-z0-9._-]/', '', $rawFolder) ?: ('v'.$newVersion);
        $targetReleaseDir = $releasesPath.DIRECTORY_SEPARATOR.$folderName;

        $activeReal = @realpath($currentLink);
        if (is_dir($targetReleaseDir)) {
            if ($activeReal !== false && realpath($targetReleaseDir) === $activeReal) {
                throw new RuntimeException("Rilis {$folderName} sudah sedang aktif.");
            }

            $this->removePath($targetReleaseDir);
        }

        $this->movePath($extractDir, $targetReleaseDir);

        try {
            $oldAssetsDir = $currentLink.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.'assets';
            $newAssetsDir = $targetReleaseDir.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.'assets';
            $this->preserveMissingFiles($oldAssetsDir, $newAssetsDir);

            $this->wireSharedResources($targetReleaseDir, $sharedPath);
            $this->clearBootstrapCacheFiles($targetReleaseDir);

            if (! $this->runPostCodeSwap($targetReleaseDir)) {
                throw new RuntimeException('Migrasi basis data atau pemanasan cache gagal pada rilis baru; symlink produksi tetap aman di rilis lama.');
            }

            $this->switchCurrentLinkAtomically($currentLink, $targetReleaseDir);
        } catch (Throwable $e) {
            if ($activeReal === false || realpath($targetReleaseDir) !== $activeReal) {
                $this->removePath($targetReleaseDir);
            }

            throw $e;
        }

        $this->pruneOldAtomicReleases($releasesPath, $currentLink, $layout['base_path']);
    }

    /**
     * @param  array<string, mixed>  $layout
     */
    private function applyStagedInplace(array $layout, string $extractDir, string $previousVersion): void
    {
        $basePath = (string) $layout['base_path'];
        $publicPath = (string) $layout['public_path'];
        $updatesRoot = $this->updatesWorkspacePath($layout);

        $safePrev = preg_replace('/[^A-Za-z0-9._-]/', '_', $previousVersion) ?: 'prev';
        $snapshotDir = $updatesRoot.DIRECTORY_SEPARATOR.'prev-'.$safePrev.'-'.now()->format('YmdHis').'-'.bin2hex(random_bytes(2));
        $coreBackupDir = $snapshotDir.DIRECTORY_SEPARATOR.'core';
        $publicBackupDir = $snapshotDir.DIRECTORY_SEPARATOR.'public_build';

        mkdir($coreBackupDir, 0775, true);
        mkdir($publicBackupDir, 0775, true);

        foreach (['manifest.json', 'fonts-manifest.json'] as $manifestName) {
            $liveManifest = $publicPath.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.$manifestName;
            if (is_file($liveManifest)) {
                @copy($liveManifest, $publicBackupDir.DIRECTORY_SEPARATOR.$manifestName);
            }
        }

        $extractedPublic = $extractDir.DIRECTORY_SEPARATOR.'public';
        if (is_dir($extractedPublic.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.'assets')) {
            $this->copyDirectoryMerge(
                $extractedPublic.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.'assets',
                $publicPath.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.'assets'
            );
        }

        if (is_dir($extractedPublic.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'tinymce')) {
            $this->copyDirectoryMerge(
                $extractedPublic.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'tinymce',
                $publicPath.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'tinymce'
            );
        }

        foreach (['manifest.json', 'fonts-manifest.json'] as $manifestName) {
            $newManifest = $extractedPublic.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.$manifestName;
            if (is_file($newManifest)) {
                $targetManifest = $publicPath.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.$manifestName;
                if (! is_dir(dirname($targetManifest))) {
                    mkdir(dirname($targetManifest), 0775, true);
                }

                @copy($newManifest, $targetManifest);
            }
        }

        $movedTargets = [];
        $addedOnlyTargets = [];

        try {
            foreach (self::CORE_SWAP_TARGETS as $relTarget) {
                $relOs = str_replace('/', DIRECTORY_SEPARATOR, $relTarget);
                $newItem = $extractDir.DIRECTORY_SEPARATOR.$relOs;
                if (! file_exists($newItem)) {
                    continue;
                }

                $liveItem = $basePath.DIRECTORY_SEPARATOR.$relOs;
                $backupItem = $coreBackupDir.DIRECTORY_SEPARATOR.$relOs;

                if (file_exists($liveItem)) {
                    if (! is_dir(dirname($backupItem))) {
                        mkdir(dirname($backupItem), 0775, true);
                    }

                    $this->movePath($liveItem, $backupItem);
                    $movedTargets[] = $relTarget;
                } else {
                    $addedOnlyTargets[] = $relTarget;
                }

                if (! is_dir(dirname($liveItem))) {
                    mkdir(dirname($liveItem), 0775, true);
                }

                $this->movePath($newItem, $liveItem);
            }

            $this->clearBootstrapCacheFiles($basePath);

            if (! $this->runPostCodeSwap($basePath)) {
                throw new RuntimeException('Migrasi atau pemanasan cache gagal setelah pertukaran berkas; perubahan dipulihkan otomatis.');
            }
        } catch (Throwable $e) {
            $this->revertStagedSnapshot($basePath, $publicPath, $snapshotDir, $movedTargets, $addedOnlyTargets);
            $this->removePath($snapshotDir);

            throw $e;
        }

        $this->pruneOldStagedSnapshots($updatesRoot);
    }

    /**
     * @param  array<string, mixed>  $layout
     */
    private function rollbackAtomicRelease(array $layout, ?string $targetRelease): string
    {
        $releasesPath = (string) $layout['releases_path'];
        $currentLink = (string) $layout['current_link'];
        $activeReal = @realpath($currentLink);

        $candidates = [];
        foreach (@scandir($releasesPath) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $fullPath = $releasesPath.DIRECTORY_SEPARATOR.$entry;
            if (! is_dir($fullPath) || ! is_file($fullPath.DIRECTORY_SEPARATOR.'version.json')) {
                continue;
            }

            if ($activeReal !== false && realpath($fullPath) === $activeReal) {
                continue;
            }

            $candidates[$entry] = $fullPath;
        }

        if ($candidates === []) {
            throw new RuntimeException('Tidak ada rilis sebelumnya di folder releases/ untuk di-rollback.');
        }

        if ($targetRelease !== null && trim($targetRelease) !== '') {
            $requested = trim($targetRelease);
            $matched = $candidates[$requested] ?? $candidates['v'.ltrim($requested, 'vV')] ?? null;
            if ($matched === null) {
                throw new RuntimeException("Rilis tujuan rollback '{$requested}' tidak ditemukan.");
            }

            $selectedDir = $matched;
        } else {
            uasort($candidates, static fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));
            $selectedDir = reset($candidates);
        }

        $this->clearBootstrapCacheFiles($selectedDir);
        $this->switchCurrentLinkAtomically($currentLink, $selectedDir);

        if (! $this->runPostCodeSwap($selectedDir)) {
            throw new RuntimeException('Pemanasan cache pasca-rollback mengalami kendala.');
        }

        return CmsVersion::current($selectedDir.DIRECTORY_SEPARATOR.'version.json');
    }

    /**
     * @param  array<string, mixed>  $layout
     */
    private function rollbackStagedInplace(array $layout, ?string $targetSnapshot): string
    {
        $basePath = (string) $layout['base_path'];
        $publicPath = (string) $layout['public_path'];
        $updatesRoot = $this->updatesWorkspacePath($layout);

        $snapshots = [];
        if (is_dir($updatesRoot)) {
            foreach (@scandir($updatesRoot) ?: [] as $entry) {
                if (! str_starts_with($entry, 'prev-')) {
                    continue;
                }

                $full = $updatesRoot.DIRECTORY_SEPARATOR.$entry;
                if (is_dir($full.DIRECTORY_SEPARATOR.'core')) {
                    $snapshots[$entry] = $full;
                }
            }
        }

        if ($snapshots === []) {
            throw new RuntimeException('Tidak ditemukan snapshot cadangan lokal untuk di-rollback.');
        }

        if ($targetSnapshot !== null && trim($targetSnapshot) !== '') {
            $selected = $snapshots[trim($targetSnapshot)] ?? null;
            if ($selected === null) {
                throw new RuntimeException("Snapshot rollback '{$targetSnapshot}' tidak ditemukan.");
            }
        } else {
            uasort($snapshots, static fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));
            $selected = reset($snapshots);
        }

        $coreBackup = $selected.DIRECTORY_SEPARATOR.'core';
        foreach (self::CORE_SWAP_TARGETS as $relTarget) {
            $relOs = str_replace('/', DIRECTORY_SEPARATOR, $relTarget);
            $backupItem = $coreBackup.DIRECTORY_SEPARATOR.$relOs;
            if (! file_exists($backupItem)) {
                continue;
            }

            $liveItem = $basePath.DIRECTORY_SEPARATOR.$relOs;
            if (file_exists($liveItem)) {
                $this->removePath($liveItem);
            }

            if (! is_dir(dirname($liveItem))) {
                mkdir(dirname($liveItem), 0775, true);
            }

            $this->movePath($backupItem, $liveItem);
        }

        $publicBackup = $selected.DIRECTORY_SEPARATOR.'public_build';
        foreach (['manifest.json', 'fonts-manifest.json'] as $manifestName) {
            $oldManifest = $publicBackup.DIRECTORY_SEPARATOR.$manifestName;
            if (is_file($oldManifest)) {
                $liveManifest = $publicPath.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.$manifestName;
                if (! is_dir(dirname($liveManifest))) {
                    mkdir(dirname($liveManifest), 0775, true);
                }

                @copy($oldManifest, $liveManifest);
            }
        }

        $this->clearBootstrapCacheFiles($basePath);
        $this->removePath($selected);

        if (! $this->runPostCodeSwap($basePath)) {
            throw new RuntimeException('Pemanasan cache pasca-rollback mengalami kendala.');
        }

        return CmsVersion::current($basePath.DIRECTORY_SEPARATOR.'version.json');
    }

    /**
     * @param  array<int, string>  $movedTargets
     * @param  array<int, string>  $addedOnlyTargets
     */
    private function revertStagedSnapshot(
        string $basePath,
        string $publicPath,
        string $snapshotDir,
        array $movedTargets,
        array $addedOnlyTargets,
    ): void {
        foreach ($addedOnlyTargets as $relTarget) {
            $liveItem = $basePath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relTarget);
            if (file_exists($liveItem)) {
                $this->removePath($liveItem);
            }
        }

        $coreBackupDir = $snapshotDir.DIRECTORY_SEPARATOR.'core';
        foreach (array_reverse($movedTargets) as $relTarget) {
            $relOs = str_replace('/', DIRECTORY_SEPARATOR, $relTarget);
            $backupItem = $coreBackupDir.DIRECTORY_SEPARATOR.$relOs;
            $liveItem = $basePath.DIRECTORY_SEPARATOR.$relOs;

            if (! file_exists($backupItem)) {
                continue;
            }

            if (file_exists($liveItem)) {
                $this->removePath($liveItem);
            }

            if (! is_dir(dirname($liveItem))) {
                @mkdir(dirname($liveItem), 0775, true);
            }

            $this->movePath($backupItem, $liveItem);
        }

        $publicBackupDir = $snapshotDir.DIRECTORY_SEPARATOR.'public_build';
        foreach (['manifest.json', 'fonts-manifest.json'] as $manifestName) {
            $oldManifest = $publicBackupDir.DIRECTORY_SEPARATOR.$manifestName;
            if (is_file($oldManifest)) {
                @copy($oldManifest, $publicPath.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.$manifestName);
            }
        }

        $this->clearBootstrapCacheFiles($basePath);
    }

    private function wireSharedResources(string $releaseDir, string $sharedPath): void
    {
        $sharedEnv = $sharedPath.DIRECTORY_SEPARATOR.'.env';
        $releaseEnv = $releaseDir.DIRECTORY_SEPARATOR.'.env';

        if (file_exists($releaseEnv) || is_link($releaseEnv)) {
            @unlink($releaseEnv);
        }

        if (is_file($sharedEnv)) {
            if (PHP_OS_FAMILY === 'Windows') {
                if (! @symlink($sharedEnv, $releaseEnv)) {
                    @copy($sharedEnv, $releaseEnv);
                }
            } else {
                symlink('../../shared/.env', $releaseEnv);
            }
        }

        $releaseStorage = $releaseDir.DIRECTORY_SEPARATOR.'storage';
        if (file_exists($releaseStorage) || $this->isDirectoryLink($releaseStorage)) {
            $this->removePath($releaseStorage);
        }

        $sharedStorage = $sharedPath.DIRECTORY_SEPARATOR.'storage';
        if (! is_dir($sharedStorage)) {
            mkdir($sharedStorage.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'public', 0775, true);
        }

        $this->createDirectoryLink($sharedStorage, $releaseStorage, '../../shared/storage');

        $publicStorage = $releaseDir.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'storage';
        if (file_exists($publicStorage) || $this->isDirectoryLink($publicStorage)) {
            $this->removePath($publicStorage);
        }

        if (! is_dir(dirname($publicStorage))) {
            mkdir(dirname($publicStorage), 0775, true);
        }

        $this->createDirectoryLink(
            $sharedStorage.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'public',
            $publicStorage,
            '../../../shared/storage/app/public'
        );
    }

    private function switchCurrentLinkAtomically(string $currentLink, string $targetReleaseDir): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            if ($this->isDirectoryLink($currentLink) || is_dir($currentLink)) {
                @rmdir($currentLink);
            } elseif (file_exists($currentLink)) {
                @unlink($currentLink);
            }

            $this->createDirectoryLink($targetReleaseDir, $currentLink, null);

            return;
        }

        $tmpLink = $currentLink.'.tmp-'.bin2hex(random_bytes(4));
        $relativeTarget = 'releases/'.basename($targetReleaseDir);

        if (! symlink($relativeTarget, $tmpLink)) {
            throw new RuntimeException('Gagal menyiapkan symlink sementara untuk rilis baru.');
        }

        if (! rename($tmpLink, $currentLink)) {
            @unlink($tmpLink);
            throw new RuntimeException('Gagal menukar symlink current secara atomik.');
        }
    }

    private function createDirectoryLink(string $absoluteTarget, string $linkPath, ?string $posixRelativeTarget): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $command = sprintf('cmd /c mklink /J %s %s >nul 2>&1', escapeshellarg($linkPath), escapeshellarg($absoluteTarget));
            exec($command, $output, $exitCode);
            if ($exitCode !== 0) {
                throw new RuntimeException("Gagal membuat tautan direktori Windows ke {$absoluteTarget}.");
            }

            return;
        }

        $target = $posixRelativeTarget ?? $absoluteTarget;
        if (! symlink($target, $linkPath)) {
            throw new RuntimeException("Gagal membuat symlink {$linkPath} -> {$target}.");
        }
    }

    private function isDirectoryLink(string $path): bool
    {
        return is_link($path) || @readlink($path) !== false;
    }

    private function runPostCodeSwap(string $targetBasePath): bool
    {
        if ($this->postCodeSwapRunner !== null) {
            return (bool) ($this->postCodeSwapRunner)($targetBasePath);
        }

        $artisanPath = $targetBasePath.DIRECTORY_SEPARATOR.'artisan';
        if (! is_file($artisanPath)) {
            return false;
        }

        $result = Process::path($targetBasePath)
            ->timeout(180)
            ->run([PHP_BINARY, $artisanPath, 'cms:update', '--local-only', '--no-interaction']);

        return $result->successful();
    }

    private function triggerWebOpcacheReset(): void
    {
        try {
            $nonce = Str::random(40);
            Cache::put('cms_opcache_nonce:'.$nonce, true, 30);

            $signedUrl = URL::temporarySignedRoute(
                'system.opcache-reset',
                now()->addSeconds(30),
                ['nonce' => $nonce]
            );

            Http::connectTimeout(2)
                ->timeout(4)
                ->withHeaders(['User-Agent' => 'SatkerCMS-Updater'])
                ->post($signedUrl);
        } catch (Throwable) {
            // Non-fatal when CLI runs while local web server is offline
        }
    }

    private function clearBootstrapCacheFiles(string $targetBasePath): void
    {
        $cacheDir = $targetBasePath.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'cache';
        if (! is_dir($cacheDir)) {
            return;
        }

        foreach (glob($cacheDir.DIRECTORY_SEPARATOR.'*.php') ?: [] as $cachedPhp) {
            @unlink($cachedPhp);
        }
    }

    private function preserveMissingFiles(string $sourceDir, string $destDir): void
    {
        if (! is_dir($sourceDir)) {
            return;
        }

        if (! is_dir($destDir) && ! mkdir($destDir, 0775, true) && ! is_dir($destDir)) {
            return;
        }

        foreach (@scandir($sourceDir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $src = $sourceDir.DIRECTORY_SEPARATOR.$entry;
            $dst = $destDir.DIRECTORY_SEPARATOR.$entry;

            if (is_dir($src)) {
                $this->preserveMissingFiles($src, $dst);
            } elseif (is_file($src) && ! file_exists($dst)) {
                @copy($src, $dst);
            }
        }
    }

    private function copyDirectoryMerge(string $sourceDir, string $destDir): void
    {
        if (! is_dir($sourceDir)) {
            return;
        }

        if (! is_dir($destDir) && ! mkdir($destDir, 0775, true) && ! is_dir($destDir)) {
            throw new RuntimeException("Gagal menyiapkan direktori aset: {$destDir}");
        }

        foreach (@scandir($sourceDir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $src = $sourceDir.DIRECTORY_SEPARATOR.$entry;
            $dst = $destDir.DIRECTORY_SEPARATOR.$entry;

            if (is_dir($src)) {
                $this->copyDirectoryMerge($src, $dst);
            } elseif (is_file($src)) {
                @copy($src, $dst);
            }
        }
    }

    private function pruneOldAtomicReleases(string $releasesPath, string $currentLink, string $previousBasePath): void
    {
        $keep = max(2, (int) config('satker.update.keep_releases', 3));
        $activeReal = @realpath($currentLink);
        $previousReal = @realpath($previousBasePath);

        $dirs = [];
        foreach (@scandir($releasesPath) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $full = $releasesPath.DIRECTORY_SEPARATOR.$entry;
            if (is_dir($full) && is_file($full.DIRECTORY_SEPARATOR.'version.json')) {
                $dirs[] = $full;
            }
        }

        if (count($dirs) <= $keep) {
            return;
        }

        usort($dirs, static fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));

        foreach (array_slice($dirs, $keep) as $staleDir) {
            $real = @realpath($staleDir);
            if ($real !== false && ($real === $activeReal || $real === $previousReal)) {
                continue;
            }

            $this->removePath($staleDir);
        }
    }

    private function pruneOldStagedSnapshots(string $updatesRoot): void
    {
        $keep = max(1, (int) config('satker.update.keep_releases', 3));
        if (! is_dir($updatesRoot)) {
            return;
        }

        $snapshots = [];
        foreach (@scandir($updatesRoot) ?: [] as $entry) {
            if (str_starts_with($entry, 'prev-')) {
                $full = $updatesRoot.DIRECTORY_SEPARATOR.$entry;
                if (is_dir($full)) {
                    $snapshots[] = $full;
                }
            }
        }

        if (count($snapshots) <= $keep) {
            return;
        }

        usort($snapshots, static fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));

        foreach (array_slice($snapshots, $keep) as $stale) {
            $this->removePath($stale);
        }
    }

    private function resolveStagedPublicPath(string $resolvedBase, bool $useAppPublicDefault): string
    {
        $override = trim((string) config('satker.update.public_path_override', ''));
        if ($override !== '') {
            return rtrim($override, '/\\');
        }

        $siblingPublicHtml = dirname($resolvedBase).DIRECTORY_SEPARATOR.'public_html';
        $siblingIndex = $siblingPublicHtml.DIRECTORY_SEPARATOR.'index.php';
        if (is_file($siblingIndex)) {
            $indexContents = (string) @file_get_contents($siblingIndex);
            if ($indexContents !== '' && str_contains($indexContents, basename($resolvedBase))) {
                return $siblingPublicHtml;
            }
        }

        return $useAppPublicDefault
            ? rtrim(public_path(), '/\\')
            : $resolvedBase.DIRECTORY_SEPARATOR.'public';
    }

    /**
     * @param  array<string, mixed>  $layout
     */
    private function assertLayoutWritable(array $layout): void
    {
        $pathsToCheck = $layout['mode'] === 'atomic_release'
            ? [(string) $layout['root_path'], (string) $layout['releases_path']]
            : [(string) $layout['base_path'], (string) $layout['public_path']];

        foreach ($pathsToCheck as $dir) {
            if (is_dir($dir) && ! is_writable($dir)) {
                throw new RuntimeException("Direktori '{$dir}' tidak memiliki izin tulis untuk user saat ini.");
            }
        }
    }

    /**
     * @param  array<string, mixed>  $layout
     */
    private function updatesWorkspacePath(array $layout): string
    {
        $storageRoot = $layout['mode'] === 'atomic_release' && is_string($layout['shared_path'])
            ? $layout['shared_path'].DIRECTORY_SEPARATOR.'storage'
            : $layout['base_path'].DIRECTORY_SEPARATOR.'storage';

        $dir = $storageRoot.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'updates';
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new RuntimeException("Gagal menyiapkan ruang kerja pembaruan di {$dir}.");
        }

        return $dir;
    }

    /**
     * @template T
     *
     * @param  array<string, mixed>  $layout
     * @param  Closure(): T  $callback
     * @return T
     */
    private function withExclusiveLock(array $layout, Closure $callback): mixed
    {
        $storageRoot = $layout['mode'] === 'atomic_release' && is_string($layout['shared_path'])
            ? $layout['shared_path'].DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'
            : $layout['base_path'].DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app';

        if (! is_dir($storageRoot)) {
            mkdir($storageRoot, 0775, true);
        }

        $lockFile = $storageRoot.DIRECTORY_SEPARATOR.'cms-update.lock';
        $handle = @fopen($lockFile, 'c+');
        if (! is_resource($handle)) {
            throw new RuntimeException('Gagal membuat berkas kunci pembaruan (cms-update.lock).');
        }

        if (! flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            throw new RuntimeException('Proses pembaruan lain sedang berjalan. Tunggu hingga selesai.');
        }

        try {
            return $callback();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function movePath(string $from, string $to): void
    {
        if (@rename($from, $to)) {
            return;
        }

        if (is_dir($from)) {
            $this->copyDirectoryMerge($from, $to);
            $this->removePath($from);

            return;
        }

        if (! @copy($from, $to)) {
            throw new RuntimeException("Gagal memindahkan berkas {$from} ke {$to}.");
        }

        @unlink($from);
    }

    private function removePath(string $path): void
    {
        if (! file_exists($path) && ! $this->isDirectoryLink($path)) {
            return;
        }

        if ($this->isDirectoryLink($path)) {
            if (PHP_OS_FAMILY === 'Windows' && is_dir($path)) {
                @rmdir($path);
            } else {
                @unlink($path);
            }

            return;
        }

        if (! is_dir($path)) {
            @unlink($path);

            return;
        }

        foreach (@scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $this->removePath($path.DIRECTORY_SEPARATOR.$entry);
        }

        @rmdir($path);
    }

    private function preloadRuntimeClasses(): void
    {
        class_exists(Process::class);
        class_exists(Http::class);
        class_exists(URL::class);
        class_exists(Cache::class);
        class_exists(Str::class);
        class_exists(ActivityLogger::class);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function logSystemActivity(string $description, array $properties): void
    {
        try {
            activity('system')
                ->withProperties($properties)
                ->log($description);
        } catch (Throwable) {
            // Activity log is non-blocking if DB connection is unavailable in isolated CLI test
        }
    }
}
