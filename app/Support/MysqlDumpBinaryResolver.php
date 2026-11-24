<?php

namespace App\Support;

final class MysqlDumpBinaryResolver
{
    public static function resolve(?string $configuredPath = null): string
    {
        if (filled($configuredPath)) {
            return self::normalizeDirectory((string) $configuredPath);
        }

        $binaryName = DIRECTORY_SEPARATOR === '\\' ? 'mysqldump.exe' : 'mysqldump';

        foreach (self::pathDirectories() as $directory) {
            if (is_file($directory.DIRECTORY_SEPARATOR.$binaryName)) {
                return self::normalizeDirectory($directory);
            }
        }

        foreach (self::commonDirectories() as $directory) {
            if (is_file($directory.DIRECTORY_SEPARATOR.$binaryName)) {
                return self::normalizeDirectory($directory);
            }
        }

        return '';
    }

    /**
     * @return array<int, string>
     */
    private static function pathDirectories(): array
    {
        $rawPath = getenv('PATH');
        if (! is_string($rawPath) || trim($rawPath) === '') {
            return [];
        }

        $directories = [];
        foreach (explode(PATH_SEPARATOR, $rawPath) as $entry) {
            $trimmed = trim($entry, " \t\n\r\0\x0B\"'");
            if ($trimmed !== '' && is_dir($trimmed)) {
                $directories[] = rtrim($trimmed, '\\/');
            }
        }

        return array_values(array_unique($directories));
    }

    /**
     * @return array<int, string>
     */
    private static function commonDirectories(): array
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $patterns = [
                'C:/laragon/bin/mysql/*/bin',
                'D:/laragon/bin/mysql/*/bin',
                'C:/xampp/mysql/bin',
                'D:/xampp/mysql/bin',
                'C:/wamp64/bin/mysql/*/bin',
                'C:/wamp64/bin/mariadb/*/bin',
                'C:/Program Files/MySQL/MySQL Server */bin',
                'C:/Program Files/MariaDB */bin',
            ];

            $candidates = [];
            foreach ($patterns as $pattern) {
                $matches = glob($pattern, GLOB_ONLYDIR);
                if (is_array($matches)) {
                    rsort($matches, SORT_NATURAL);
                    foreach ($matches as $match) {
                        $candidates[] = rtrim($match, '\\/');
                    }
                }
            }

            return array_values(array_unique($candidates));
        }

        return array_values(array_filter([
            '/usr/bin',
            '/usr/local/bin',
            '/usr/local/mysql/bin',
            '/opt/homebrew/bin',
            '/opt/homebrew/opt/mysql-client/bin',
        ], static fn (string $dir): bool => is_dir($dir)));
    }

    private static function normalizeDirectory(string $directory): string
    {
        $trimmed = trim($directory, " \t\n\r\0\x0B\"'");
        if ($trimmed === '') {
            return '';
        }

        if (is_file($trimmed)) {
            $trimmed = dirname($trimmed);
        }

        return rtrim(str_replace('\\', '/', $trimmed), '/');
    }
}
