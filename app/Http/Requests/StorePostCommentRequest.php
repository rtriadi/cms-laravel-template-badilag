<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePostCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merges = [];

        if ($this->has('author_name')) {
            $strippedName = strip_tags((string) $this->input('author_name'));
            $merges['author_name'] = trim((string) preg_replace('/\s+/', ' ', $strippedName));
        }

        if ($this->has('body')) {
            $strippedBody = str_replace(["\r\n", "\r"], "\n", strip_tags((string) $this->input('body')));
            $merges['body'] = trim((string) preg_replace('/\n{3,}/', "\n\n", $strippedBody));
        }

        if ($merges !== []) {
            $this->merge($merges);
        }
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'author_name' => ['required', 'string', 'min:2', 'max:120'],
            'author_email' => ['nullable', 'email', 'max:160'],
            'body' => ['required', 'string', 'min:5', 'max:1500'],
            'captcha' => ['required', 'string', 'max:10'],
            'website_hp' => ['nullable', 'max:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'author_name.required' => 'Nama lengkap wajib diisi.',
            'author_name.min' => 'Nama lengkap minimal 2 karakter.',
            'author_name.max' => 'Nama lengkap maksimal 120 karakter.',
            'author_email.email' => 'Format alamat surel tidak valid.',
            'author_email.max' => 'Alamat surel maksimal 160 karakter.',
            'body.required' => 'Isi komentar wajib diisi.',
            'body.min' => 'Isi komentar minimal 5 karakter.',
            'body.max' => 'Isi komentar maksimal 1.500 karakter.',
            'captcha.required' => 'Kode keamanan captcha wajib diisi.',
            'website_hp.max' => 'Pengiriman ditolak oleh filter keamanan.',
        ];
    }
}
