<?php

namespace App\Support;

class SafeHtml
{
    /**
     * Sanitize rich text HTML content while keeping safe structural markup.
     */
    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        // Remove dangerous container elements including their inner content
        $sanitized = (string) preg_replace(
            '#<(script|style|iframe|object|embed|svg|form)\b[^>]*>.*?</\1>#is',
            '',
            $html
        );

        // Remove standalone self-closing dangerous tags
        $sanitized = (string) preg_replace(
            '#<(script|style|iframe|object|embed|svg|form)\b[^>]*?/?>#is',
            '',
            $sanitized
        );

        // Keep only safe structural and typographic tags
        $sanitized = strip_tags(
            $sanitized,
            '<p><br><ul><ol><li><strong><em><b><i><h3><h4><a><table><thead><tbody><tr><th><td><blockquote>'
        );

        // Strip inline event handlers like onclick= or onerror=
        $sanitized = (string) preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/iu', '', $sanitized);

        // Neutralize javascript: and data: URIs inside href attributes
        $sanitized = (string) preg_replace('/href\s*=\s*("|\')\s*(javascript|data|vbscript):[^"\']*\1/iu', 'href="#"', $sanitized);

        return $sanitized;
    }

    /**
     * Format post/article body cleanly whether written as HTML or plain text.
     */
    public static function cleanBody(?string $body): string
    {
        if ($body === null || trim($body) === '') {
            return '';
        }

        if (preg_match('/<(p|div|ul|ol|h[1-6]|table|blockquote)\b/i', $body) === 1) {
            return self::clean($body);
        }

        return nl2br(e($body));
    }

    /**
     * Allow only safe internal relative paths (/...) or valid http(s) URLs.
     */
    public static function cleanUrl(?string $url): ?string
    {
        $trimmed = trim((string) $url);

        if ($trimmed === '') {
            return null;
        }

        // Reject protocol-relative //evil.example URLs and control characters
        if (preg_match('/[\x00-\x1F\x7F]/', $trimmed) === 1 || str_starts_with($trimmed, '//')) {
            return null;
        }

        if (str_starts_with($trimmed, '/')) {
            return $trimmed;
        }

        $scheme = strtolower((string) parse_url($trimmed, PHP_URL_SCHEME));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        if (filter_var($trimmed, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return $trimmed;
    }
}
