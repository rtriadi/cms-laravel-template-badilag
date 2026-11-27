<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

class SafeUpload
{
    /**
     * Validate uploaded PDF magic header (%PDF-) and reject active script/launch tokens.
     *
     * Returns an Indonesian error message if invalid, or null when safe.
     */
    public static function validatePdf(UploadedFile $file): ?string
    {
        $realPath = $file->getRealPath();

        if (! is_string($realPath) || $realPath === '' || ! is_file($realPath)) {
            return 'Berkas PDF tidak dapat dibaca oleh peladen.';
        }

        $handle = @fopen($realPath, 'rb');
        if ($handle === false) {
            return 'Berkas PDF tidak dapat dibuka untuk validasi keamanan.';
        }

        $header = (string) fread($handle, 16);
        fclose($handle);

        $normalizedHeader = ltrim($header, "\xEF\xBB\xBF\r\n\t ");
        if (! str_starts_with($normalizedHeader, '%PDF-')) {
            return 'Berkas bukan dokumen PDF yang valid (header %PDF- tidak ditemukan).';
        }

        $contents = @file_get_contents($realPath);
        if (! is_string($contents) || $contents === '') {
            return 'Isi dokumen PDF kosong atau rusak.';
        }

        if (preg_match('/\/(?:JavaScript|JS|Launch|RichMedia)(?=[\s\/\(\[<>\r\n]|$)/', $contents) === 1) {
            return 'Dokumen PDF ditolak karena mengandung skrip aktif atau perintah eksekusi yang tidak diizinkan.';
        }

        return null;
    }
}
