<?php

namespace App\Services;

use App\Support\SafeHtml;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class SimtepaJsonService
{
    public const string PHOTO_BASE_URL = 'https://img.badilag.net/cache/foto_formal/300/';

    /**
     * Fetch, sanitize, and cache public official profiles from an official SIMTEPA Share Data JSON endpoint.
     *
     * Strictly omits sensitive personal data (NIP, birth date/place, religion, personal contact)
     * in accordance with SK Dirjen Badilag public privacy guidelines.
     *
     * @return array<int, array{
     *     nama_gelar: string,
     *     jabatan: string,
     *     pangkat_ruang: string,
     *     tmt_jabatan: string,
     *     photo_url: ?string,
     *     riwayat_pendidikan_html: string,
     *     riwayat_pekerjaan_html: string,
     * }>|null
     */
    public function fetchProfiles(?string $jsonUrl): ?array
    {
        if (! $this->isAllowedSimtepaJsonUrl($jsonUrl)) {
            return null;
        }

        if (app()->runningUnitTests() && ! config('satker.simtepa_fetch_in_tests', false)) {
            return null;
        }

        $cacheKey = 'simtepa.native.profiles.v1.'.sha1((string) $jsonUrl);
        $cached = Cache::get($cacheKey);

        if (is_array($cached) && array_key_exists('ok', $cached)) {
            return $cached['ok'] ? $cached['profiles'] : null;
        }

        try {
            $response = Http::connectTimeout(2)
                ->timeout(5)
                ->withoutRedirecting()
                ->acceptJson()
                ->withHeaders(['User-Agent' => 'SatkerCMS/1.0'])
                ->get((string) $jsonUrl);

            if (! $response->successful()) {
                Cache::put($cacheKey, ['ok' => false, 'profiles' => []], now()->addMinutes(10));

                return null;
            }

            $payload = $response->json();
            $profiles = $this->normalizePayload($payload);

            Cache::put($cacheKey, ['ok' => true, 'profiles' => $profiles], now()->addHours(2));

            return $profiles;
        } catch (Throwable) {
            Cache::put($cacheKey, ['ok' => false, 'profiles' => []], now()->addMinutes(10));

            return null;
        }
    }

    public function isAllowedSimtepaJsonUrl(?string $url): bool
    {
        if (! is_string($url) || trim($url) === '') {
            return false;
        }

        $parts = parse_url(trim($url));
        if (! is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');

        if (isset($parts['port']) || isset($parts['user']) || isset($parts['pass']) || str_contains($path, '..')) {
            return false;
        }

        return $scheme === 'https'
            && $host === 'simtepa.mahkamahagung.go.id'
            && str_starts_with($path, '/share/')
            && str_contains($path, '/json/');
    }

    /**
     * @return array<int, array{
     *     nama_gelar: string,
     *     jabatan: string,
     *     pangkat_ruang: string,
     *     tmt_jabatan: string,
     *     photo_url: ?string,
     *     riwayat_pendidikan_html: string,
     *     riwayat_pekerjaan_html: string,
     * }>
     */
    private function normalizePayload(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        $rows = array_is_list($payload)
            ? $payload
            : (is_array($payload['data'] ?? null) ? $payload['data'] : []);

        $profiles = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['nama_gelar'] ?? $row['nama_lengkap'] ?? $row['nama'] ?? ''));
            if ($name === '') {
                continue;
            }

            $jabatan = trim((string) ($row['jabatan'] ?? ''));
            $pangkat = trim((string) ($row['pangkat_ruang'] ?? ''));
            $golongan = trim((string) ($row['golongan'] ?? ''));
            $pangkatDisplay = $pangkat !== ''
                ? ($golongan !== '' && ! str_contains($pangkat, $golongan) ? "{$pangkat} ({$golongan})" : $pangkat)
                : $golongan;

            $tmt = trim((string) ($row['tmt_jabatan_terakhir'] ?? ''));
            $photoRaw = trim((string) ($row['FotoFormal'] ?? $row['FotoPegawai'] ?? $row['foto'] ?? ''));
            $photoUrl = $this->resolvePhotoUrl($photoRaw);

            $eduRaw = trim((string) ($row['riwayat_pendidikan'] ?? ''));
            $workRaw = trim((string) ($row['riwayat_pekerjaan'] ?? ''));

            $profiles[] = [
                'nama_gelar' => $name,
                'jabatan' => $jabatan,
                'pangkat_ruang' => $pangkatDisplay,
                'tmt_jabatan' => $tmt,
                'photo_url' => $photoUrl,
                'riwayat_pendidikan_html' => $eduRaw !== '' ? SafeHtml::clean($eduRaw) : '',
                'riwayat_pekerjaan_html' => $workRaw !== '' ? SafeHtml::clean($workRaw) : '',
            ];
        }

        return $profiles;
    }

    private function resolvePhotoUrl(string $raw): ?string
    {
        if ($raw === '' || str_contains($raw, '..') || str_contains($raw, '\\')) {
            return null;
        }

        if (str_starts_with($raw, 'https://')) {
            $host = strtolower((string) parse_url($raw, PHP_URL_HOST));
            if (in_array($host, ['img.badilag.net', 'simtepa.mahkamahagung.go.id'], true)) {
                return SafeHtml::cleanUrl($raw);
            }

            return null;
        }

        $filename = basename($raw);
        if (preg_match('/^[A-Za-z0-9._-]+\.(?:jpe?g|png|webp)$/i', $filename) !== 1) {
            return null;
        }

        return self::PHOTO_BASE_URL.$filename;
    }
}
