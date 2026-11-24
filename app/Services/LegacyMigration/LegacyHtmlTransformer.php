<?php

declare(strict_types=1);

namespace App\Services\LegacyMigration;

use App\Support\SafeHtml;
use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\Str;

class LegacyHtmlTransformer
{
    /** @var array<string, int> */
    private const array INDONESIAN_MONTHS = [
        'januari' => 1,
        'jan' => 1,
        'februari' => 2,
        'pebruari' => 2,
        'feb' => 2,
        'maret' => 3,
        'mar' => 3,
        'april' => 4,
        'apr' => 4,
        'mei' => 5,
        'juni' => 6,
        'jun' => 6,
        'juli' => 7,
        'jul' => 7,
        'agustus' => 8,
        'agu' => 8,
        'agt' => 8,
        'september' => 9,
        'sep' => 9,
        'oktober' => 10,
        'okt' => 10,
        'november' => 11,
        'nopember' => 11,
        'nov' => 11,
        'nop' => 11,
        'desember' => 12,
        'des' => 12,
    ];

    /** @var list<string> */
    private const array GENERIC_LINK_LABELS = [
        'unduh',
        'unduh dokumen',
        'download',
        'download file',
        'dokumen',
        'dokumen relaas',
        'relaas',
        'lihat',
        'lihat file',
        'buka',
        'klik disini',
        'klik di sini',
        'disini',
        'di sini',
        'file',
        'pdf',
        'link',
        'link preview',
        'preview',
    ];

    /**
     * Decode Joomla Sourcerer `{source}...{/source}` HTML-entity-escaped blocks into parseable HTML,
     * stripping `<style>` and `<script>` blocks safely.
     */
    public function unwrapSourcerer(string $html): string
    {
        if (! str_contains(strtolower($html), '{source')) {
            return $html;
        }

        $unwrapped = (string) preg_replace_callback(
            '/(?:<p[^>]*>\s*(?:<span[^>]*>\s*)?)?\{source(?:\s+[^}]*)?\}(?:\s*<\/span>)?(?:\s*<\/p>)?(.*?)(?:<p[^>]*>\s*(?:<span[^>]*>\s*)?)?\{\/source\}(?:\s*<\/span>)?(?:\s*<\/p>)?/is',
            static function (array $matches): string {
                $inner = (string) ($matches[1] ?? '');
                // Remove TinyMCE wrapper markup around escaped Sourcerer entities
                $plain = strip_tags($inner);
                $decoded = html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                // Strip raw <style> and <script> blocks inside Sourcerer snippets
                $decoded = (string) preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $decoded);
                $decoded = (string) preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $decoded);

                return trim($decoded);
            },
            $html,
        );

        // Clean any stray unclosed {source} tags
        return (string) preg_replace('/\{\/?source(?:\s+[^}]*)?\}/i', '', $unwrapped);
    }

    /**
     * Clean legacy Joomla HTML, optimize and rewrite local images & PDFs, strip duplicate hero image,
     * and sanitize output via SafeHtml::clean().
     *
     * @return array{html: string, first_local_image: ?string, external_images: list<string>}
     */
    public function cleanRichHtml(
        string $html,
        LegacyMediaSanitizer $media,
        string $fallbackAlt,
        ?string $stripFirstLocalImgSrc = null,
        string $pdfCollectionType = 'regulasi_sk',
    ): array {
        $prepared = $this->stripLegacyNoise($this->unwrapSourcerer($html));
        if (trim($prepared) === '') {
            return [
                'html' => '',
                'first_local_image' => null,
                'external_images' => [],
            ];
        }

        $dom = $this->loadHtmlDom($prepared);
        $root = $this->findRootContainer($dom);
        if ($root === null) {
            return [
                'html' => '',
                'first_local_image' => null,
                'external_images' => [],
            ];
        }

        $firstLocalImage = null;
        $externalImages = [];
        $normalizedStripSrc = $stripFirstLocalImgSrc !== null ? $this->normalizeComparablePath($stripFirstLocalImgSrc) : null;
        $strippedOnce = false;

        /** @var list<DOMElement> $images */
        $images = [];
        foreach ($dom->getElementsByTagName('img') as $imgNode) {
            $images[] = $imgNode;
        }

        foreach ($images as $img) {
            $rawSrc = trim($img->getAttribute('src'));
            if ($rawSrc === '' || str_starts_with(strtolower($rawSrc), 'data:')) {
                $this->removeNodeCleanly($img);

                continue;
            }

            if ($this->isBrokenLegacyDecoration($rawSrc)) {
                $this->removeNodeCleanly($img);

                continue;
            }

            if ($this->isExternalUrl($rawSrc)) {
                $externalImages[] = $rawSrc;
                // Strict CSP `img-src 'self' data: blob: https:` blocks plain http:// external images.
                if (str_starts_with(strtolower($rawSrc), 'http://')) {
                    $this->removeImageAndEmptyAncestors($img);

                    continue;
                }
                $cleanExt = SafeHtml::cleanUrl($rawSrc);
                if ($cleanExt === null) {
                    $this->removeNodeCleanly($img);

                    continue;
                }
                $img->setAttribute('src', $cleanExt);
                $img->setAttribute('loading', 'lazy');
                $img->setAttribute('decoding', 'async');
                $this->ensureAccessibleAlt($img, $fallbackAlt);

                continue;
            }

            $normalized = $this->normalizeComparablePath($rawSrc);
            if ($firstLocalImage === null) {
                $firstLocalImage = $rawSrc;
            }

            if (
                ! $strippedOnce
                && $normalizedStripSrc !== null
                && $normalized === $normalizedStripSrc
            ) {
                $strippedOnce = true;
                $this->removeImageAndEmptyAncestors($img);

                continue;
            }

            $storedPath = $media->processImage($rawSrc, 'editor');
            if ($storedPath === null) {
                $this->removeNodeCleanly($img);

                continue;
            }

            $img->setAttribute('src', '/storage/'.ltrim($storedPath, '/'));
            $img->setAttribute('loading', 'lazy');
            $img->setAttribute('decoding', 'async');
            $this->ensureAccessibleAlt($img, $fallbackAlt);
        }

        /** @var list<DOMElement> $anchors */
        $anchors = [];
        foreach ($dom->getElementsByTagName('a') as $anchorNode) {
            $anchors[] = $anchorNode;
        }

        foreach ($anchors as $anchor) {
            $href = trim($anchor->getAttribute('href'));
            if ($href === '') {
                continue;
            }

            if (! $this->isExternalUrl($href) && preg_match('#^http://(?:www\.)?pa-gorontalo\.go\.id/?(.*)$#i', $href, $ownMatch) === 1) {
                $relPath = '/'.ltrim((string) ($ownMatch[1] ?? ''), '/');
                $anchor->setAttribute('href', $relPath === '/' ? '/' : 'https://pa-gorontalo.go.id'.$relPath);
                $href = $anchor->getAttribute('href');
            }

            if (! $this->isExternalUrl($href) && preg_match('/\.pdf(?:[?#].*)?$/i', $href) === 1) {
                $storedPdf = $media->processLocalPdf($href, $pdfCollectionType);
                if ($storedPdf !== null) {
                    $anchor->setAttribute('href', '/storage/'.ltrim($storedPdf, '/'));
                    $anchor->setAttribute('target', '_blank');
                    $anchor->setAttribute('rel', 'noopener noreferrer');
                }
            } elseif (str_contains(strtolower($href), 'drive.google.com')) {
                $canonical = $this->canonicalizeExternalDocUrl($href);
                if ($canonical !== null) {
                    $anchor->setAttribute('href', $canonical);
                    $anchor->setAttribute('target', '_blank');
                    $anchor->setAttribute('rel', 'noopener noreferrer');
                }
            }
        }

        $output = $this->serializeRootChildren($dom, $root);
        $cleanHtml = SafeHtml::clean($output);

        return [
            'html' => $cleanHtml,
            'first_local_image' => $firstLocalImage,
            'external_images' => array_values(array_unique($externalImages)),
        ];
    }

    /**
     * Extract structured document records (local PDFs + Google Drive + external PDFs) from Joomla HTML tables & lists.
     *
     * @return list<array{
     *     collection_type: string,
     *     title: string,
     *     year: int,
     *     month: ?int,
     *     status_label: ?string,
     *     file_path: ?string,
     *     external_url: ?string,
     *     metadata: array<string, string>,
     *     is_published: bool,
     *     created_at: ?string
     * }>
     */
    public function extractDocumentsFromHtml(
        string $html,
        string $collectionType,
        string $defaultTitle,
        int $fallbackYear,
        LegacyMediaSanitizer $media,
        ?string $sourceDate = null,
    ): array {
        $prepared = $this->stripLegacyNoise($this->unwrapSourcerer($html));
        if (trim($prepared) === '') {
            return [];
        }

        $dom = $this->loadHtmlDom($prepared);
        $root = $this->findRootContainer($dom);
        if ($root === null) {
            return [];
        }

        $documents = [];
        $seenKeys = [];
        $contextYear = $this->clampYear($fallbackYear);
        $contextHeading = '';

        $this->walkForDocuments(
            $root,
            $collectionType,
            $defaultTitle,
            $fallbackYear,
            $sourceDate,
            $media,
            $contextYear,
            $contextHeading,
            $documents,
            $seenKeys,
        );

        return $documents;
    }

    /**
     * Canonicalize Google Drive and external document URLs for deduplication and VARCHAR(255) safety.
     */
    public function canonicalizeExternalDocUrl(string $rawUrl): ?string
    {
        $url = trim(html_entity_decode($rawUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($url === '') {
            return null;
        }

        // Normalize Google Drive file links (/file/d/{ID}/view, /preview, /edit, /open?id={ID}, /uc?id={ID})
        if (preg_match('#^https?://drive\.google\.com/file/d/([A-Za-z0-9_-]{15,})#i', $url, $m) === 1) {
            return "https://drive.google.com/file/d/{$m[1]}/view";
        }

        if (preg_match('~^https?://drive\.google\.com/(?:open|uc)\?[^#]*\bid=([A-Za-z0-9_-]{15,})~i', $url, $m) === 1) {
            return "https://drive.google.com/file/d/{$m[1]}/view";
        }

        // Normalize Google Drive folder links
        if (preg_match('#^https?://drive\.google\.com/drive/(?:u/\d+/)?folders/([A-Za-z0-9_-]{15,})#i', $url, $m) === 1) {
            return "https://drive.google.com/drive/folders/{$m[1]}";
        }

        $clean = SafeHtml::cleanUrl($url);
        if ($clean === null || strlen($clean) > 250) {
            return null;
        }

        return $clean;
    }

    /**
     * Extract the first local image path from raw Joomla HTML (used to promote featured image before cleaning).
     */
    public function extractFirstLocalImageSrc(string $html): ?string
    {
        if (preg_match_all('/<img\b[^>]*\bsrc=["\']([^"\']+)["\']/i', $html, $matches) > 0) {
            foreach ($matches[1] as $src) {
                $src = trim($src);
                if (
                    $src === ''
                    || str_starts_with(strtolower($src), 'data:')
                    || $this->isExternalUrl($src)
                    || $this->isBrokenLegacyDecoration($src)
                ) {
                    continue;
                }

                return $src;
            }
        }

        return null;
    }

    /**
     * Build a clean plain-text excerpt from HTML without stray UTF-8 non-breaking spaces.
     */
    public function extractExcerpt(string $html, int $maxLength = 220): ?string
    {
        $stripped = strip_tags($this->stripLegacyNoise($this->unwrapSourcerer($html)));
        $decoded = html_entity_decode($stripped, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = trim((string) preg_replace('/[\s\xC2\xA0\p{Z}]+/u', ' ', $decoded));

        if ($plain === '') {
            return null;
        }

        return Str::limit($plain, $maxLength, '...');
    }

    /**
     * Remove the first inline `<img>` inside already-cleaned HTML when its filename duplicates `featured_image_path`,
     * along with any non-HTTPS (`http://`) CSP-blocked external `<img>` tags and bare legacy `http://pa-gorontalo.go.id` links.
     */
    public function sanitizePostBodyDuplicates(string $html, ?string $featuredImagePath): string
    {
        if (trim($html) === '') {
            return '';
        }

        $featuredBase = $featuredImagePath !== null && trim($featuredImagePath) !== ''
            ? strtolower(basename($featuredImagePath))
            : null;

        $dom = $this->loadHtmlDom($html);
        $root = $this->findRootContainer($dom);
        if ($root === null) {
            return $html;
        }

        $strippedFeatured = false;
        /** @var list<DOMElement> $images */
        $images = [];
        foreach ($dom->getElementsByTagName('img') as $imgNode) {
            $images[] = $imgNode;
        }

        foreach ($images as $img) {
            $src = trim($img->getAttribute('src'));
            if (str_starts_with(strtolower($src), 'http://')) {
                $this->removeImageAndEmptyAncestors($img);

                continue;
            }

            if (! $strippedFeatured && $featuredBase !== null) {
                $srcPath = (string) parse_url($src, PHP_URL_PATH);
                if (strtolower(basename($srcPath)) === $featuredBase) {
                    $strippedFeatured = true;
                    $this->removeImageAndEmptyAncestors($img);
                }
            }
        }

        foreach ($dom->getElementsByTagName('a') as $anchor) {
            $href = trim($anchor->getAttribute('href'));
            if (preg_match('#^http://(?:www\.)?pa-gorontalo\.go\.id/?(.*)$#i', $href, $m) === 1) {
                $rel = '/'.ltrim((string) ($m[1] ?? ''), '/');
                $anchor->setAttribute('href', $rel === '/' ? '/' : 'https://pa-gorontalo.go.id'.$rel);
            }
        }

        $output = $this->serializeRootChildren($dom, $root);

        return SafeHtml::clean($output);
    }

    /**
     * @param  list<array<string, mixed>>  $documents
     * @param  array<string, bool>  $seenKeys
     */
    private function walkForDocuments(
        DOMNode $node,
        string $collectionType,
        string $defaultTitle,
        int $fallbackYear,
        ?string $sourceDate,
        LegacyMediaSanitizer $media,
        int &$contextYear,
        string &$contextHeading,
        array &$documents,
        array &$seenKeys,
    ): void {
        if (! $node instanceof DOMElement) {
            return;
        }

        $tag = strtolower($node->tagName);

        if (in_array($tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'strong', 'caption'], true)) {
            $text = $this->cleanText($node->textContent);
            if ($text !== '' && strlen($text) <= 140) {
                $detectedYear = $this->extractYear($text);
                if ($detectedYear !== null) {
                    $contextYear = $detectedYear;
                }
                if (! $this->isGenericLabel($text) && ! str_starts_with(strtolower($text), 'http')) {
                    $contextHeading = $text;
                }
            }
        }

        if ($tag === 'table') {
            $this->extractFromTable($node, $collectionType, $defaultTitle, $fallbackYear, $sourceDate, $media, $contextYear, $contextHeading, $documents, $seenKeys);

            return;
        }

        if ($tag === 'a') {
            $this->tryAppendDocumentFromAnchor(
                $node,
                $collectionType,
                $defaultTitle,
                $contextHeading,
                [],
                $contextYear,
                $fallbackYear,
                $sourceDate,
                $media,
                $documents,
                $seenKeys,
            );

            return;
        }

        foreach ($node->childNodes as $child) {
            $this->walkForDocuments(
                $child,
                $collectionType,
                $defaultTitle,
                $fallbackYear,
                $sourceDate,
                $media,
                $contextYear,
                $contextHeading,
                $documents,
                $seenKeys,
            );
        }
    }

    /**
     * @param  list<array<string, mixed>>  $documents
     * @param  array<string, bool>  $seenKeys
     */
    private function extractFromTable(
        DOMElement $table,
        string $collectionType,
        string $defaultTitle,
        int $fallbackYear,
        ?string $sourceDate,
        LegacyMediaSanitizer $media,
        int &$contextYear,
        string &$contextHeading,
        array &$documents,
        array &$seenKeys,
    ): void {
        /** @var list<string> $headers */
        $headers = [];
        $rows = $table->getElementsByTagName('tr');

        foreach ($rows as $rowIndex => $tr) {
            /** @var list<DOMElement> $cells */
            $cells = [];
            foreach ($tr->childNodes as $child) {
                if ($child instanceof DOMElement && in_array(strtolower($child->tagName), ['td', 'th'], true)) {
                    $cells[] = $child;
                }
            }

            if ($cells === []) {
                continue;
            }

            $cellTexts = array_map(fn (DOMElement $c): string => $this->cleanText($c->textContent), $cells);
            $anchorsInRow = $tr->getElementsByTagName('a');

            // Header row or year banner row (no anchors)
            if ($anchorsInRow->length === 0) {
                $joined = trim(implode(' ', array_filter($cellTexts)));
                $yr = $this->extractYear($joined);
                if ($yr !== null) {
                    $contextYear = $yr;
                }
                if ($rowIndex <= 1 || strtolower($cells[0]->tagName) === 'th') {
                    $headers = $cellTexts;
                }
                if ($joined !== '' && strlen($joined) <= 120) {
                    $contextHeading = $joined;
                }

                continue;
            }

            // Update year from header cells if row contains explicit year column
            $rowNonLinkParts = [];
            foreach ($cells as $cIdx => $cell) {
                $cAnchors = $cell->getElementsByTagName('a');
                $txt = $cellTexts[$cIdx];
                if ($cAnchors->length === 0 && $txt !== '' && ! preg_match('/^\d{1,3}\.?$/', $txt)) {
                    $rowNonLinkParts[] = $txt;
                }
            }

            foreach ($cells as $cIdx => $cell) {
                $colHeader = $headers[$cIdx] ?? '';
                $colYear = $this->extractYear($colHeader);
                $effectiveContextYear = $colYear ?? $contextYear;

                foreach ($cell->getElementsByTagName('a') as $anchor) {
                    $rowHints = $rowNonLinkParts;
                    if ($colHeader !== '' && ! $this->isGenericLabel($colHeader) && strtolower($colHeader) !== 'link' && strtolower($colHeader) !== 'file') {
                        $rowHints[] = $colHeader;
                    }

                    $this->tryAppendDocumentFromAnchor(
                        $anchor,
                        $collectionType,
                        $defaultTitle,
                        $contextHeading,
                        $rowHints,
                        $effectiveContextYear,
                        $fallbackYear,
                        $sourceDate,
                        $media,
                        $documents,
                        $seenKeys,
                    );
                }
            }
        }
    }

    /**
     * @param  list<string>  $rowHints
     * @param  list<array<string, mixed>>  $documents
     * @param  array<string, bool>  $seenKeys
     */
    private function tryAppendDocumentFromAnchor(
        DOMElement $anchor,
        string $collectionType,
        string $defaultTitle,
        string $contextHeading,
        array $rowHints,
        int $contextYear,
        int $fallbackYear,
        ?string $sourceDate,
        LegacyMediaSanitizer $media,
        array &$documents,
        array &$seenKeys,
    ): void {
        $rawHref = trim($anchor->getAttribute('href'));
        if ($rawHref === '' || str_starts_with($rawHref, '#') || str_starts_with(strtolower($rawHref), 'javascript:') || str_starts_with(strtolower($rawHref), 'mailto:')) {
            return;
        }

        $isGoogleDrive = str_contains(strtolower($rawHref), 'drive.google.com');
        $isPdfLink = preg_match('/\.pdf(?:[?#].*)?$/i', $rawHref) === 1;
        $isDropbox = str_contains(strtolower($rawHref), 'dropbox.com');

        if (! $isGoogleDrive && ! $isPdfLink && ! $isDropbox) {
            return;
        }

        $filePath = null;
        $externalUrl = null;

        if (! $this->isExternalUrl($rawHref) && $isPdfLink) {
            $filePath = $media->processLocalPdf($rawHref, $collectionType);
            if ($filePath === null) {
                return;
            }
        } else {
            $externalUrl = $this->canonicalizeExternalDocUrl($rawHref);
            if ($externalUrl === null) {
                return;
            }
        }

        $dedupKey = $collectionType.'|'.($filePath ?? $externalUrl);
        if (isset($seenKeys[$dedupKey])) {
            return;
        }
        $seenKeys[$dedupKey] = true;

        $anchorText = $this->cleanText($anchor->textContent);
        $title = $this->composeDocumentTitle($defaultTitle, $anchorText, $rowHints, $contextHeading);

        $combinedForDate = trim($anchorText.' '.implode(' ', $rowHints).' '.$contextHeading.' '.$title);
        $year = $this->extractYear($anchorText)
            ?? $this->extractYear(implode(' ', $rowHints))
            ?? $this->extractYear($contextHeading)
            ?? $contextYear
            ?? $fallbackYear;
        $year = $this->clampYear($year);

        $month = $this->extractMonth($anchorText) ?? $this->extractMonth(implode(' ', $rowHints));
        $statusLabel = $this->detectStatusLabel($combinedForDate, $externalUrl);
        $metadata = $this->extractDocumentMetadata($collectionType, $combinedForDate, $rowHints);

        $documents[] = [
            'collection_type' => $collectionType,
            'title' => Str::limit($title, 240, '...'),
            'year' => $year,
            'month' => $month,
            'status_label' => $statusLabel,
            'file_path' => $filePath,
            'external_url' => $externalUrl,
            'metadata' => $metadata,
            'is_published' => true,
            'created_at' => $sourceDate,
        ];
    }

    /**
     * @param  list<string>  $rowHints
     */
    private function composeDocumentTitle(string $defaultTitle, string $anchorText, array $rowHints, string $contextHeading): string
    {
        $cleanAnchor = (! $this->isGenericLabel($anchorText) && ! str_starts_with(strtolower($anchorText), 'http'))
            ? $anchorText
            : '';

        $parts = [];
        foreach ($rowHints as $hint) {
            $h = trim($hint);
            if ($h !== '' && ! $this->isGenericLabel($h) && ! str_starts_with(strtolower($h), 'http')) {
                $parts[] = $h;
            }
        }

        if ($cleanAnchor !== '' && ! in_array($cleanAnchor, $parts, true)) {
            $parts[] = $cleanAnchor;
        }

        $parts = array_values(array_unique($parts));
        $joined = implode(' - ', $parts);

        if ($joined === '') {
            if ($contextHeading !== '' && strcasecmp($contextHeading, $defaultTitle) !== 0) {
                return "{$defaultTitle} - {$contextHeading}";
            }

            return $defaultTitle;
        }

        if (stripos($joined, $defaultTitle) !== false || strlen($joined) > 65) {
            return $joined;
        }

        return "{$defaultTitle} - {$joined}";
    }

    /**
     * @param  list<string>  $rowHints
     * @return array<string, string>
     */
    private function extractDocumentMetadata(string $collectionType, string $text, array $rowHints): array
    {
        $meta = [];

        if ($collectionType === 'panggilan_ghaib') {
            if (preg_match('/\b(\d{1,5}\/Pdt\.[GP]\/\d{4}\/PA\.[A-Za-z.]+)\b/i', $text, $m) === 1) {
                $meta['nomor_perkara'] = $m[1];
            }
            foreach ($rowHints as $hint) {
                if (preg_match('/\b(senin|selasa|rabu|kamis|jumat|sabtu|minggu|\d{1,2}\s+(?:jan|feb|peb|mar|apr|mei|jun|jul|agu|sep|okt|nov|nop|des))/iu', $hint) === 1) {
                    $meta['tanggal_sidang'] = Str::limit($hint, 80, '');
                }
            }
        }

        return $meta;
    }

    private function detectStatusLabel(string $text, ?string $externalUrl): ?string
    {
        if (preg_match('/\b(DIPA[-\s]?0[14])\b/i', $text, $m) === 1) {
            return strtoupper(str_replace(' ', '-', $m[1]));
        }

        if (preg_match('/\b(Triwulan\s+(?:I{1,3}|IV|[1-4])|Semester\s+(?:I{1,2}|[12]))\b/i', $text, $m) === 1) {
            return Str::title($m[1]);
        }

        if (stripos($text, 'reviu') !== false || stripos($text, 'revisi') !== false) {
            return 'Reviu';
        }

        if ($externalUrl !== null && str_contains($externalUrl, '/drive/folders/')) {
            return 'Arsip Folder';
        }

        return 'Final';
    }

    private function extractYear(string $text): ?int
    {
        if (preg_match('/\b(20[0-2]\d|199\d)\b/', $text, $m) === 1) {
            return $this->clampYear((int) $m[1]);
        }

        return null;
    }

    private function extractMonth(string $text): ?int
    {
        $lower = strtolower($text);
        foreach (self::INDONESIAN_MONTHS as $name => $monthNum) {
            if (preg_match('/\b'.preg_quote($name, '/').'\b/i', $lower) === 1) {
                return $monthNum;
            }
        }

        return null;
    }

    private function clampYear(int $year): int
    {
        $maxYear = (int) date('Y') + 1;

        return max(1990, min($maxYear, $year));
    }

    private function stripLegacyNoise(string $html): string
    {
        $cleaned = (string) preg_replace('/<!--\[if[^\]]*\]>.*?<!\[endif\]-->/is', '', $html);
        $cleaned = (string) preg_replace('/<xml\b[^>]*>.*?<\/xml>/is', '', $cleaned);
        $cleaned = (string) preg_replace('/\{(?:loadmodule|loadposition|module|tab|\/tabs|rokcandy)[^}]*\}/i', '', $cleaned);

        return $cleaned;
    }

    private function loadHtmlDom(string $html): DOMDocument
    {
        $prev = libxml_use_internal_errors(true);

        try {
            $dom = new DOMDocument('1.0', 'UTF-8');
            $wrapped = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><div data-migration-root="1">'.$html.'</div></body></html>';
            $dom->loadHTML($wrapped, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

            return $dom;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($prev);
        }
    }

    private function findRootContainer(DOMDocument $dom): ?DOMElement
    {
        foreach ($dom->getElementsByTagName('div') as $div) {
            if ($div->getAttribute('data-migration-root') === '1') {
                return $div;
            }
        }

        return null;
    }

    private function serializeRootChildren(DOMDocument $dom, DOMElement $root): string
    {
        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= (string) $dom->saveHTML($child);
        }

        return trim($out);
    }

    private function isExternalUrl(string $url): bool
    {
        if (preg_match('#^https?://#i', $url) !== 1) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $host !== '' && $host !== 'pa-gorontalo.go.id' && $host !== 'www.pa-gorontalo.go.id';
    }

    private function isBrokenLegacyDecoration(string $src): bool
    {
        $lower = strtolower($src);

        return str_contains($lower, 'media/sourcerer/images/tab.png')
            || str_contains($lower, 'download-as-pdf.png')
            || str_contains($lower, 'images/banners/white.png');
    }

    private function normalizeComparablePath(string $src): string
    {
        $path = $src;
        if (preg_match('#^https?://#i', $path) === 1) {
            $path = (string) parse_url($path, PHP_URL_PATH);
        }

        $path = (string) strtok($path, '?#');

        return strtolower(ltrim(rawurldecode(str_replace('\\', '/', $path)), '/'));
    }

    private function removeImageAndEmptyAncestors(DOMElement $img): void
    {
        $ancestor = $img->parentNode;
        $this->removeNodeCleanly($img);

        $collapsibleTags = ['a', 'span', 'b', 'strong', 'em', 'i', 'u', 'p', 'div', 'figure'];

        while ($ancestor instanceof DOMElement && $ancestor->getAttribute('data-migration-root') !== '1') {
            $tag = strtolower($ancestor->tagName);
            if (! in_array($tag, $collapsibleTags, true)) {
                break;
            }

            $remainingText = trim((string) preg_replace('/[\s\xC2\xA0\p{Z}]+/u', '', $ancestor->textContent));
            if ($remainingText !== '' || $ancestor->getElementsByTagName('img')->length > 0) {
                break;
            }

            // Keep non-empty anchor links unless they had only the removed image
            if ($tag !== 'a' && $ancestor->getElementsByTagName('a')->length > 0) {
                break;
            }

            $nextAncestor = $ancestor->parentNode;
            $this->removeNodeCleanly($ancestor);
            $ancestor = $nextAncestor;
        }
    }

    private function removeNodeCleanly(DOMNode $node): void
    {
        if ($node->parentNode !== null) {
            $node->parentNode->removeChild($node);
        }
    }

    private function ensureAccessibleAlt(DOMElement $img, string $fallbackAlt): void
    {
        $alt = trim($img->getAttribute('alt'));
        if (
            $alt === ''
            || preg_match('/^(?:img|whatsapp[\s_-]+image|dsc|photo|image|screenshot|msedge)[\s_0-9-]*$/i', $alt) === 1
        ) {
            $img->setAttribute('alt', Str::limit($fallbackAlt, 140, ''));
        }
    }

    private function isGenericLabel(string $label): bool
    {
        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $label) ?? ''));
        if ($normalized === '' || preg_match('/^[\d.\s-]+$/', $normalized) === 1) {
            return true;
        }

        return in_array($normalized, self::GENERIC_LINK_LABELS, true);
    }

    private function cleanText(string $text): string
    {
        $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalized = str_replace("\xC2\xA0", ' ', $decoded);

        return trim((string) preg_replace('/\s+/', ' ', $normalized));
    }
}
