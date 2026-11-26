<?php

namespace App\Support;

class CmsVersion
{
    public const string DEFAULT_MIN_PHP = '8.4.1';

    public static function current(?string $path = null): string
    {
        return self::fromFile($path)['version'];
    }

    /**
     * @return array{version: string, commit: ?string, released_at: ?string, min_php: string}
     */
    public static function fromFile(?string $path = null): array
    {
        $fallbackVersion = self::normalize((string) config('satker.cms_version', '1.0.0-dev'));
        $resolvedPath = $path ?? base_path('version.json');

        $defaults = [
            'version' => $fallbackVersion,
            'commit' => $path === null ? self::resolveGitHeadCommit() : null,
            'released_at' => null,
            'min_php' => self::DEFAULT_MIN_PHP,
        ];

        if (! is_file($resolvedPath) || ! is_readable($resolvedPath)) {
            return $defaults;
        }

        $raw = @file_get_contents($resolvedPath);
        if (! is_string($raw) || trim($raw) === '') {
            return $defaults;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return $defaults;
        }

        $version = isset($decoded['version']) && is_string($decoded['version']) && trim($decoded['version']) !== ''
            ? self::normalize($decoded['version'])
            : $fallbackVersion;

        $commit = isset($decoded['commit']) && is_string($decoded['commit']) && preg_match('/^[a-fA-F0-9]{7,40}$/', trim($decoded['commit'])) === 1
            ? strtolower(trim($decoded['commit']))
            : ($path === null ? self::resolveGitHeadCommit() : null);

        $releasedAt = isset($decoded['released_at']) && is_string($decoded['released_at']) && trim($decoded['released_at']) !== ''
            ? trim($decoded['released_at'])
            : null;

        $minPhp = isset($decoded['min_php']) && is_string($decoded['min_php']) && trim($decoded['min_php']) !== ''
            ? self::normalize($decoded['min_php'])
            : self::DEFAULT_MIN_PHP;

        return [
            'version' => $version,
            'commit' => $commit,
            'released_at' => $releasedAt,
            'min_php' => $minPhp,
        ];
    }

    public static function normalize(string $version): string
    {
        $trimmed = trim($version);
        if ($trimmed === '') {
            return '1.0.0-dev';
        }

        if (preg_match('/^[vV](\d.*)$/', $trimmed, $matches) === 1) {
            return $matches[1];
        }

        return $trimmed;
    }

    private static function resolveGitHeadCommit(): ?string
    {
        $headFile = base_path('.git/HEAD');
        if (! is_file($headFile) || ! is_readable($headFile)) {
            return null;
        }

        $head = trim((string) @file_get_contents($headFile));
        if (preg_match('/^[a-fA-F0-9]{40}$/', $head) === 1) {
            return strtolower($head);
        }

        if (str_starts_with($head, 'ref:')) {
            $refRel = ltrim(trim(substr($head, 4)), '/\\');
            if ($refRel !== '' && ! str_contains($refRel, '..')) {
                $refFile = base_path('.git/'.$refRel);
                if (is_file($refFile) && is_readable($refFile)) {
                    $sha = trim((string) @file_get_contents($refFile));
                    if (preg_match('/^[a-fA-F0-9]{40}$/', $sha) === 1) {
                        return strtolower($sha);
                    }
                }
            }
        }

        return null;
    }
}
