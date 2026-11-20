<?php

namespace App\Support;

class SeoText
{
    /**
     * Build a clean meta description truncated at a word boundary without exceeding maxLength.
     */
    public static function excerpt(?string $text, int $maxLength = 155): string
    {
        $clean = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) ($text ?? ''))));
        if ($clean === '' || mb_strlen($clean) <= $maxLength) {
            return $clean;
        }

        $budget = max(20, $maxLength - 3);
        $slice = mb_substr($clean, 0, $budget + 1);
        $lastSpace = mb_strrpos($slice, ' ');

        if ($lastSpace !== false && $lastSpace >= (int) ($budget * 0.6)) {
            $slice = mb_substr($slice, 0, $lastSpace);
        } else {
            $slice = mb_substr($clean, 0, $budget);
        }

        return rtrim($slice, " \t\n\r\0\x0B.,;:-").'...';
    }

    /**
     * Build an HTML document title without duplicating the satker name when already included.
     */
    public static function pageTitle(?string $title, string $satkerName): string
    {
        $cleanTitle = trim((string) ($title ?? ''));
        $cleanSatker = trim($satkerName);

        if ($cleanTitle === '') {
            return $cleanSatker;
        }

        if ($cleanSatker !== '' && mb_stripos($cleanTitle, $cleanSatker) !== false) {
            return $cleanTitle;
        }

        return $cleanSatker !== '' ? "{$cleanTitle} - {$cleanSatker}" : $cleanTitle;
    }
}
