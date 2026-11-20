<?php

namespace App\Support;

use Carbon\CarbonInterface;

final class Tanggal
{
    public static function format(?CarbonInterface $date, string $format = 'd F Y, H:i'): string
    {
        return $date?->copy()->locale(app()->getLocale())->translatedFormat($format) ?? '-';
    }
}
