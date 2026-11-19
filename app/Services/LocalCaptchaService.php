<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;

class LocalCaptchaService
{
    public const string SESSION_KEY = 'local_captcha_hash';

    public function generateCode(): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $code = '';

        for ($i = 0; $i < 5; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        session([self::SESSION_KEY => Hash::make($code)]);

        return $code;
    }

    public function verify(string $input): bool
    {
        $hash = session()->pull(self::SESSION_KEY);

        if (! is_string($hash) || $hash === '' || trim($input) === '') {
            return false;
        }

        return Hash::check(strtoupper(trim($input)), $hash);
    }

    public function generateSvg(?string $code = null): string
    {
        $chars = str_split($code ?? $this->generateCode());

        $svgParts = [
            '<svg xmlns="http://www.w3.org/2000/svg" width="180" height="48" viewBox="0 0 180 48" role="img" aria-label="Kode keamanan captcha">',
            '<rect width="180" height="48" rx="6" fill="#F0F7F4" stroke="#056E4F" stroke-width="1"/>',
        ];

        for ($i = 0; $i < 4; $i++) {
            $y1 = random_int(8, 40);
            $y2 = random_int(8, 40);
            $svgParts[] = "<line x1=\"8\" y1=\"{$y1}\" x2=\"172\" y2=\"{$y2}\" stroke=\"#A7F3D0\" stroke-width=\"1.5\"/>";
        }

        foreach ($chars as $idx => $char) {
            $x = 24 + ($idx * 30);
            $y = random_int(30, 35);
            $rot = random_int(-12, 12);
            $escaped = htmlspecialchars($char, ENT_XML1);
            $svgParts[] = "<text x=\"{$x}\" y=\"{$y}\" font-family=\"monospace\" font-size=\"22\" font-weight=\"700\" fill=\"#033D2C\" transform=\"rotate({$rot} {$x} {$y})\">{$escaped}</text>";
        }

        $svgParts[] = '</svg>';

        return implode('', $svgParts);
    }
}
