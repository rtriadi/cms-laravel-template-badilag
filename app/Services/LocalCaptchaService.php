<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;

class LocalCaptchaService
{
    public const string SESSION_KEY = 'local_captcha_hash';

    public const string SESSION_EXPIRES_KEY = 'local_captcha_expires_at';

    private const string HMAC_PREFIX = 'hmac:';

    /**
     * Indonesian number words for screen-reader friendly arithmetic challenges
     * that avoid leaking plaintext answers on /captcha-text.
     *
     * @var array<int, string>
     */
    private const array NUMBER_WORDS = [
        1 => 'satu',
        2 => 'dua',
        3 => 'tiga',
        4 => 'empat',
        5 => 'lima',
        6 => 'enam',
        7 => 'tujuh',
        8 => 'delapan',
        9 => 'sembilan',
        10 => 'sepuluh',
        11 => 'sebelas',
        12 => 'dua belas',
        13 => 'tiga belas',
        14 => 'empat belas',
        15 => 'lima belas',
    ];

    public function generateCode(): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $code = '';

        for ($i = 0; $i < 5; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        $this->storeAnswer($code);

        return $code;
    }

    /**
     * Generate a screen-reader friendly Indonesian arithmetic question without exposing the raw answer.
     */
    public function generateAccessibleChallenge(): string
    {
        $isAddition = random_int(0, 1) === 1;

        if ($isAddition) {
            $left = random_int(2, 9);
            $right = random_int(2, 9);
            $answer = (string) ($left + $right);
            $prompt = 'Berapa hasil '.self::NUMBER_WORDS[$left].' ditambah '.self::NUMBER_WORDS[$right].'? (ketik angka)';
        } else {
            $left = random_int(7, 15);
            $right = random_int(2, 6);
            $answer = (string) ($left - $right);
            $prompt = 'Berapa hasil '.self::NUMBER_WORDS[$left].' dikurangi '.self::NUMBER_WORDS[$right].'? (ketik angka)';
        }

        $this->storeAnswer($answer);

        return $prompt;
    }

    private function storeAnswer(string $answer): void
    {
        session([
            self::SESSION_KEY => $this->signCode($answer),
            self::SESSION_EXPIRES_KEY => now()->addMinutes(10)->timestamp,
        ]);
    }

    public function verify(string $input): bool
    {
        $stored = session()->pull(self::SESSION_KEY);
        $expiresAt = session()->pull(self::SESSION_EXPIRES_KEY);
        $normalized = strtoupper(trim($input));

        if (! is_string($stored) || $stored === '' || $normalized === '') {
            return false;
        }

        if (is_int($expiresAt) && $expiresAt < now()->timestamp) {
            return false;
        }

        if (str_starts_with($stored, self::HMAC_PREFIX)) {
            return hash_equals($stored, $this->signCode($normalized));
        }

        return Hash::check($normalized, $stored);
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

    private function signCode(string $code): string
    {
        $key = (string) config('app.key');
        if ($key === '') {
            throw new \RuntimeException('APP_KEY wajib dikonfigurasi untuk penandatanganan captcha.');
        }

        return self::HMAC_PREFIX.hash_hmac('sha256', strtoupper($code), $key);
    }
}
