<?php

declare(strict_types=1);

namespace Database\Seeders\LegacyPaGorontalo;

use App\Models\Document;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DocumentsSeeder extends Seeder
{
    /**
     * Legacy document hosts verified to serve valid HTTPS TLS certificates.
     *
     * @var list<string>
     */
    private const array VERIFIED_HTTPS_HOSTS = [
        'pta-gorontalo.net',
        'www.pta-gorontalo.net',
        'pa-bantul.go.id',
        'www.pa-bantul.go.id',
        'pta-jakarta.go.id',
        'www.pta-jakarta.go.id',
        'pa-gorontalo.go.id',
        'www.pa-gorontalo.go.id',
    ];

    /** @var array<int, string> */
    private const array MONTH_NAMES = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public function run(): void
    {
        if (! LegacyBundleReader::exists('documents.json')) {
            return;
        }

        $rawDocuments = LegacyBundleReader::readJson('documents.json');
        $allowedCollections = Document::collectionTypes();
        $prepared = [];

        foreach ($rawDocuments as $row) {
            if (! is_array($row)) {
                continue;
            }

            $collection = (string) ($row['collection_type'] ?? '');
            if (! array_key_exists($collection, $allowedCollections)) {
                continue;
            }

            $filePath = isset($row['file_path']) && trim((string) $row['file_path']) !== ''
                ? trim((string) $row['file_path'])
                : null;
            $rawExternalUrl = isset($row['external_url']) && trim((string) $row['external_url']) !== ''
                ? trim((string) $row['external_url'])
                : null;

            if ($filePath === null && $rawExternalUrl === null) {
                continue;
            }

            $externalUrl = $rawExternalUrl !== null
                ? $this->upgradeVerifiedHttpsUrl($rawExternalUrl)
                : null;

            $year = max(1990, min((int) date('Y') + 1, (int) ($row['year'] ?? date('Y'))));
            $month = isset($row['month']) && $row['month'] !== null ? max(1, min(12, (int) $row['month'])) : null;
            $title = $this->cleanDocumentTitle((string) ($row['title'] ?? 'Dokumen Resmi'));

            $metadata = is_array($row['metadata'] ?? null) ? $row['metadata'] : null;
            if ($metadata === []) {
                $metadata = null;
            }

            $prepared[] = [
                'collection_type' => $collection,
                'title' => $title,
                'year' => $year,
                'month' => $month,
                'status_label' => isset($row['status_label']) ? substr((string) $row['status_label'], 0, 60) : 'Final',
                'file_path' => $filePath,
                'external_url' => $externalUrl,
                'legacy_external_url' => $rawExternalUrl !== $externalUrl ? $rawExternalUrl : null,
                'metadata' => $metadata,
                'is_published' => (bool) ($row['is_published'] ?? true),
                'created_at' => ! empty($row['created_at']) ? (string) $row['created_at'] : null,
            ];
        }

        $prepared = $this->disambiguateDuplicateTitles($prepared);

        Document::withoutEvents(function () use ($prepared): void {
            foreach ($prepared as $item) {
                if ($item['file_path'] !== null) {
                    $doc = Document::query()->firstOrNew([
                        'collection_type' => $item['collection_type'],
                        'file_path' => $item['file_path'],
                    ]);
                } else {
                    $doc = Document::query()
                        ->where('collection_type', $item['collection_type'])
                        ->where('external_url', $item['external_url'])
                        ->first();

                    if ($doc === null && $item['legacy_external_url'] !== null) {
                        $doc = Document::query()
                            ->where('collection_type', $item['collection_type'])
                            ->where('external_url', $item['legacy_external_url'])
                            ->first();
                    }

                    $doc ??= new Document([
                        'collection_type' => $item['collection_type'],
                        'external_url' => $item['external_url'],
                    ]);
                }

                $doc->fill([
                    'collection_type' => $item['collection_type'],
                    'title' => $item['title'],
                    'year' => $item['year'],
                    'month' => $item['month'],
                    'status_label' => $item['status_label'],
                    'file_path' => $item['file_path'],
                    'external_url' => $item['external_url'],
                    'metadata' => $item['metadata'],
                    'is_published' => $item['is_published'],
                ]);

                if (! $doc->exists && $item['created_at'] !== null) {
                    $doc->created_at = $item['created_at'];
                    $doc->updated_at = $item['created_at'];
                }

                $doc->save();
            }
        });
    }

    private function upgradeVerifiedHttpsUrl(string $url): string
    {
        if (! str_starts_with(strtolower($url), 'http://')) {
            return $url;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (in_array($host, self::VERIFIED_HTTPS_HOSTS, true)) {
            return (string) preg_replace('#^http://#i', 'https://', $url);
        }

        return $url;
    }

    private function cleanDocumentTitle(string $rawTitle): string
    {
        $title = html_entity_decode($rawTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $title = str_replace(['—', '–'], '-', $title);
        $title = (string) preg_replace('/[\s\xC2\xA0\p{Z}]+/u', ' ', $title);
        $title = trim($title);

        $title = (string) preg_replace('/^(?:link\s+preview|preview|unduh\s+dokumen|download\s+file)\s*[-:|]\s*/i', '', $title);
        $title = (string) preg_replace('/\s*[-:|]\s*(?:unduh\s+dokumen|link\s+preview|download\s+file|preview|klik\s+di\s*sini)\s*$/i', '', $title);
        $title = (string) preg_replace('/\s*-\s*-\s*/', ' - ', $title);
        $title = trim($title, " \t\n\r\0\x0B-:|");

        if ($title === '') {
            return 'Dokumen Resmi';
        }

        return Str::limit($title, 240, '');
    }

    /**
     * Ensure documents sharing the same `(collection_type, year, title)` have distinct, readable titles.
     *
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function disambiguateDuplicateTitles(array $items): array
    {
        /** @var array<string, list<int>> $groups */
        $groups = [];
        foreach ($items as $idx => $item) {
            $key = $item['collection_type'].'|'.$item['year'].'|'.mb_strtolower((string) $item['title']);
            $groups[$key][] = $idx;
        }

        foreach ($groups as $indices) {
            if (count($indices) <= 1) {
                continue;
            }

            $usedTitles = [];
            foreach ($indices as $position => $idx) {
                $baseTitle = (string) $items[$idx]['title'];
                $suffix = null;

                $month = $items[$idx]['month'];
                if (is_int($month) && isset(self::MONTH_NAMES[$month])) {
                    $monthName = self::MONTH_NAMES[$month];
                    if (! str_contains(mb_strtolower($baseTitle), mb_strtolower($monthName))) {
                        $suffix = $monthName;
                    }
                }

                if ($suffix === null) {
                    $stem = $this->extractReadableFileStem(
                        $items[$idx]['file_path'] ?? $items[$idx]['external_url'] ?? null,
                    );
                    if ($stem !== null && ! str_contains(mb_strtolower($baseTitle), mb_strtolower($stem))) {
                        $suffix = $stem;
                    }
                }

                $candidate = $suffix !== null
                    ? Str::limit($baseTitle, 195, '').' - '.$suffix
                    : Str::limit($baseTitle, 215, '').' (Dokumen '.($position + 1).')';

                $candidateKey = mb_strtolower($candidate);
                if (isset($usedTitles[$candidateKey])) {
                    $candidate = Str::limit($baseTitle, 215, '').' (Dokumen '.($position + 1).')';
                    $candidateKey = mb_strtolower($candidate);
                }

                $usedTitles[$candidateKey] = true;
                $items[$idx]['title'] = Str::limit($candidate, 240, '');
            }
        }

        return $items;
    }

    private function extractReadableFileStem(?string $source): ?string
    {
        if ($source === null || $source === '') {
            return null;
        }

        $path = (string) parse_url($source, PHP_URL_PATH);
        if (! preg_match('/\.pdf$/i', $path)) {
            return null;
        }

        $stem = rawurldecode((string) pathinfo($path, PATHINFO_FILENAME));
        $stem = (string) preg_replace('/-[a-f0-9]{8}$/i', '', $stem);
        $stem = (string) preg_replace('/[_-]+/', ' ', $stem);
        $stem = trim((string) preg_replace('/\s+/', ' ', $stem));

        if (mb_strlen($stem) < 4 || preg_match('/^\d+$/', $stem) === 1) {
            return null;
        }

        return Str::limit($stem, 40, '');
    }
}
