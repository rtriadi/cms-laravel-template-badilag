<?php

namespace App\Support;

use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMProcessingInstruction;

class SafeHtml
{
    /**
     * Allowed HTML tags from the rich-text editor.
     *
     * @var array<string, list<string>>
     */
    private const array ALLOWED_TAGS = [
        'p' => [],
        'br' => [],
        'ul' => [],
        'ol' => ['start', 'type', 'reversed'],
        'li' => [],
        'strong' => [],
        'em' => [],
        'b' => [],
        'i' => [],
        'u' => [],
        's' => [],
        'sub' => [],
        'sup' => [],
        'h1' => [],
        'h2' => [],
        'h3' => [],
        'h4' => [],
        'h5' => [],
        'h6' => [],
        'a' => ['href', 'target', 'rel'],
        'img' => ['src', 'alt', 'width', 'height', 'loading', 'decoding'],
        'figure' => [],
        'figcaption' => [],
        'table' => [],
        'thead' => [],
        'tbody' => [],
        'tfoot' => [],
        'tr' => [],
        'th' => ['colspan', 'rowspan', 'scope'],
        'td' => ['colspan', 'rowspan'],
        'blockquote' => [],
        'hr' => [],
        'pre' => [],
        'code' => [],
        'span' => [],
        'div' => [],
    ];

    /**
     * Global non-executable attributes allowed on any permitted tag.
     *
     * @var list<string>
     */
    private const array GLOBAL_ALLOWED_ATTRS = ['class', 'title', 'dir', 'lang'];

    /**
     * Dangerous elements whose entire subtree must be discarded.
     *
     * @var list<string>
     */
    private const array DROP_SUBTREE_TAGS = [
        'script',
        'style',
        'iframe',
        'object',
        'embed',
        'svg',
        'form',
        'math',
        'template',
        'noscript',
        'textarea',
        'title',
        'base',
        'meta',
        'link',
    ];

    /**
     * Sanitize rich text HTML content using a DOM allowlist parser while keeping safe structural markup.
     */
    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        if (! class_exists(DOMDocument::class)) {
            return e($html);
        }

        $previousState = libxml_use_internal_errors(true);

        try {
            $dom = new DOMDocument('1.0', 'UTF-8');
            $wrapped = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><div data-safehtml-root="1">'.$html.'</div></body></html>';
            $dom->loadHTML($wrapped, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

            $root = null;
            foreach ($dom->getElementsByTagName('div') as $div) {
                if ($div->getAttribute('data-safehtml-root') === '1') {
                    $root = $div;
                    break;
                }
            }

            if ($root === null) {
                return '';
            }

            self::sanitizeNodeChildren($root);

            $output = '';
            foreach ($root->childNodes as $child) {
                $output .= (string) $dom->saveHTML($child);
            }

            return trim($output);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousState);
        }
    }

    /**
     * Format post/article body cleanly whether written as HTML or plain text.
     */
    public static function cleanBody(?string $body): string
    {
        if ($body === null || trim($body) === '') {
            return '';
        }

        if (preg_match('/<(p|div|ul|ol|h[1-6]|table|blockquote|img|a|figure|strong|em|u|s|span|br|hr|pre)\b/i', $body) === 1) {
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

        if (
            preg_match('/[\x00-\x1F\x7F]/', $trimmed) === 1
            || str_starts_with($trimmed, '//')
            || str_contains($trimmed, '\\')
        ) {
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

    /**
     * Neutralize spreadsheet formula triggers (=, +, -, @, tab, CR) before writing to CSV.
     */
    public static function sanitizeCsvCell(mixed $value): string
    {
        $text = (string) ($value ?? '');

        if ($text !== '' && preg_match('/^[\t\r\n ]*[=+\-@]|^[\t\r]/', $text) === 1) {
            return "'".$text;
        }

        return $text;
    }

    private static function sanitizeNodeChildren(DOMNode $parent): void
    {
        $children = [];
        foreach ($parent->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child instanceof DOMComment || $child instanceof DOMProcessingInstruction) {
                $parent->removeChild($child);

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP_SUBTREE_TAGS, true)) {
                $parent->removeChild($child);

                continue;
            }

            self::sanitizeNodeChildren($child);

            if (! array_key_exists($tag, self::ALLOWED_TAGS)) {
                while ($child->firstChild !== null) {
                    $parent->insertBefore($child->firstChild, $child);
                }
                $parent->removeChild($child);

                continue;
            }

            $allowedAttrs = [...self::GLOBAL_ALLOWED_ATTRS, ...self::ALLOWED_TAGS[$tag]];
            $attrNames = [];
            if ($child->hasAttributes()) {
                foreach ($child->attributes as $attr) {
                    $attrNames[] = $attr->name;
                }
            }

            foreach ($attrNames as $attrName) {
                $lowerAttr = strtolower($attrName);
                if (! in_array($lowerAttr, $allowedAttrs, true)) {
                    $child->removeAttribute($attrName);
                }
            }

            if ($tag === 'a') {
                self::sanitizeAnchorElement($child);
            } elseif ($tag === 'img') {
                if (! self::sanitizeImageElement($child)) {
                    $parent->removeChild($child);
                }
            } elseif ($tag === 'table') {
                self::wrapResponsiveTable($child);
            }
        }
    }

    private static function wrapResponsiveTable(DOMElement $table): void
    {
        $parent = $table->parentNode;
        if (! $parent instanceof DOMElement || $table->ownerDocument === null) {
            return;
        }

        if (strtolower($parent->tagName) === 'div' && str_contains($parent->getAttribute('class'), 'prose-table-wrap')) {
            return;
        }

        $wrapper = $table->ownerDocument->createElement('div');
        $wrapper->setAttribute('class', 'prose-table-wrap');
        $parent->insertBefore($wrapper, $table);
        $wrapper->appendChild($table);
    }

    private static function sanitizeAnchorElement(DOMElement $element): void
    {
        if ($element->hasAttribute('href')) {
            $rawHref = trim($element->getAttribute('href'));
            $safeHref = self::isSafeFragment($rawHref) ? $rawHref : self::cleanUrl($rawHref);

            if ($safeHref === null) {
                $element->setAttribute('href', '#');
            } else {
                $element->setAttribute('href', $safeHref);
            }
        }

        if ($element->hasAttribute('target')) {
            $target = strtolower(trim($element->getAttribute('target')));
            if ($target === '_blank') {
                $element->setAttribute('target', '_blank');
                $element->setAttribute('rel', 'noopener noreferrer');
            } elseif ($target === '_self') {
                $element->setAttribute('target', '_self');
            } else {
                $element->removeAttribute('target');
            }
        }
    }

    private static function sanitizeImageElement(DOMElement $element): bool
    {
        if (! $element->hasAttribute('src')) {
            return false;
        }

        $safeSrc = self::cleanUrl($element->getAttribute('src'));
        if ($safeSrc === null) {
            return false;
        }

        $element->setAttribute('src', $safeSrc);

        return true;
    }

    private static function isSafeFragment(string $href): bool
    {
        return preg_match('/^#[A-Za-z0-9._-]*$/', $href) === 1;
    }
}
