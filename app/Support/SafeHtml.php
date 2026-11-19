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
}
