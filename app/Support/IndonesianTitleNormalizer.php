<?php

declare(strict_types=1);

namespace App\Support;

class IndonesianTitleNormalizer
{
    /**
     * Exact uppercase or mixed-case tokens preserved during Indonesian Title Case normalization.
     *
     * @var array<string, string>
     */
    private const array PRESERVED_TOKENS = [
        'pa' => 'PA',
        'pta' => 'PTA',
        'ma' => 'MA',
        'ri' => 'RI',
        'mari' => 'MARI',
        'ma-ri' => 'MA-RI',
        'sipp' => 'SIPP',
        'simtepa' => 'SIMTEPA',
        'siwas' => 'SIWAS',
        'sinova' => 'SINOVA',
        'sipeta' => 'SIPETA',
        'e-court' => 'e-Court',
        'ecourt' => 'e-Court',
        'e-litigation' => 'e-Litigation',
        'e-filing' => 'e-Filing',
        'e-register' => 'e-Register',
        'e-keuangan' => 'e-Keuangan',
        'e-monev' => 'e-Monev',
        'zi' => 'ZI',
        'wbk' => 'WBK',
        'wbbm' => 'WBBM',
        'ptsp' => 'PTSP',
        'dipa' => 'DIPA',
        'lra' => 'LRA',
        'lkjip' => 'LKjIP',
        'lakip' => 'LAKIP',
        'calk' => 'CALK',
        'pbj' => 'PBJ',
        'lhkpn' => 'LHKPN',
        'lhkasn' => 'LHKASN',
        'sk' => 'SK',
        'se' => 'SE',
        'sop' => 'SOP',
        'ikm' => 'IKM',
        'ipk' => 'IPK',
        'ipak' => 'IPAK',
        'ipkp' => 'IPKP',
        'skm' => 'SKM',
        'spak' => 'SPAK',
        'sakip' => 'SAKIP',
        'akip' => 'AKIP',
        'ppid' => 'PPID',
        'asn' => 'ASN',
        'cpns' => 'CPNS',
        'pppk' => 'PPPK',
        'ppnpn' => 'PPNPN',
        'pns' => 'PNS',
        'hut' => 'HUT',
        'kpk' => 'KPK',
        'bpn' => 'BPN',
        'kua' => 'KUA',
        'bkm' => 'BKM',
        'dprd' => 'DPRD',
        'dpr' => 'DPR',
        'mpr' => 'MPR',
        'kppn' => 'KPPN',
        'djkn' => 'DJKN',
        'kpknl' => 'KPKNL',
        'baznas' => 'BAZNAS',
        'upz' => 'UPZ',
        'dwp' => 'DWP',
        'ikahi' => 'IKAHI',
        'ipaspi' => 'IPASPI',
        'ptwp' => 'PTWP',
        'kpa' => 'KPA',
        'wkpa' => 'WKPA',
        'kpta' => 'KPTA',
        'wkpta' => 'WKPTA',
        'ym' => 'YM',
        'ia' => 'IA',
        'ib' => 'IB',
        'ii' => 'II',
        'iii' => 'III',
        'iv' => 'IV',
        'v' => 'V',
        'vi' => 'VI',
        'vii' => 'VII',
        'viii' => 'VIII',
        'ix' => 'IX',
        'x' => 'X',
        'xi' => 'XI',
        'xii' => 'XII',
        'psp' => 'PSP',
        'lke' => 'LKE',
        'pmpzi' => 'PMPZI',
        'tlhp' => 'TLHP',
        'bmn' => 'BMN',
        'rkt' => 'RKT',
        'pk' => 'PK',
        'iku' => 'IKU',
        'sppt' => 'SPPT',
        'ti' => 'TI',
        'it' => 'IT',
        'wita' => 'WITA',
        'wib' => 'WIB',
        'wit' => 'WIT',
        'apbn' => 'APBN',
        'pnbp' => 'PNBP',
        'rka-kl' => 'RKA-KL',
        'rkak-l' => 'RKAK-L',
        'umkm' => 'UMKM',
        'bumn' => 'BUMN',
        'bumd' => 'BUMD',
        'bri' => 'BRI',
        'bsi' => 'BSI',
        'btn' => 'BTN',
        'bni' => 'BNI',
        'mou' => 'MoU',
        'pks' => 'PKS',
        'fgd' => 'FGD',
        'apm' => 'APM',
        'smap' => 'SMAP',
        'spbe' => 'SPBE',
        'kip' => 'KIP',
        'ham' => 'HAM',
        'kdrt' => 'KDRT',
        'covid-19' => 'COVID-19',
        'covid' => 'COVID',
        's.h' => 'S.H',
        'm.h' => 'M.H',
        's.ag' => 'S.Ag',
        's.h.i' => 'S.H.I',
        'm.h.i' => 'M.H.I',
        's.kom' => 'S.Kom',
        's.e' => 'S.E',
        'm.m' => 'M.M',
        'm.si' => 'M.Si',
        'dr' => 'Dr',
        'drs' => 'Drs',
        'dra' => 'Dra',
        'h' => 'H',
        'hj' => 'Hj',
        'pdt.g' => 'Pdt.G',
        'pdt.p' => 'Pdt.P',
        'g' => 'G',
        'p' => 'P',
        'pa.gtlo' => 'PA.Gtlo',
        'bnn' => 'BNN',
        'tni' => 'TNI',
        'polri' => 'POLRI',
        'polres' => 'Polres',
        'polda' => 'Polda',
        'skp' => 'SKP',
        'simari' => 'SIMARI',
        'sikep' => 'SIKEP',
        'komdanas' => 'KOMDANAS',
        'sakti' => 'SAKTI',
        'span' => 'SPAN',
        'om-span' => 'OM-SPAN',
        'lpj' => 'LPJ',
        'spm' => 'SPM',
        'sp2d' => 'SP2D',
        'kuhp' => 'KUHP',
        'kuhap' => 'KUHAP',
        'khi' => 'KHI',
        'perma' => 'PERMA',
        'sema' => 'SEMA',
        'pp' => 'PP',
        'uu' => 'UU',
        'uud' => 'UUD',
        'rb' => 'RB',
        'bkn' => 'BKN',
        'lan' => 'LAN',
        'bpk' => 'BPK',
        'bpkp' => 'BPKP',
        'lbh' => 'LBH',
        'bphn' => 'BPHN',
        'stqh' => 'STQH',
        'mtq' => 'MTQ',
        'phbi' => 'PHBI',
        'korpri' => 'KORPRI',
        'duk' => 'DUK',
        'dus' => 'DUS',
        'upt' => 'UPT',
        'sdm' => 'SDM',
        'atk' => 'ATK',
        'bawas' => 'Bawas',
        'badilag' => 'Badilag',
        'posbakum' => 'Posbakum',
        'prodeo' => 'Prodeo',
        'zoom' => 'Zoom',
        'daring' => 'Daring',
        'luring' => 'Luring',
    ];

    /** @var list<string> */
    private const array LOWERCASE_CONNECTORS = [
        'di',
        'ke',
        'dari',
        'dan',
        'yang',
        'atau',
        'untuk',
        'pada',
        'dalam',
        'dengan',
        'oleh',
        'atas',
        'tentang',
        'sebagai',
        'serta',
        'bagi',
        'agar',
        'antara',
        'terhadap',
        'melalui',
        'secara',
        'guna',
        'demi',
        'hingga',
        'sampai',
        'tanpa',
        'namun',
        'tetapi',
        'karena',
        'sehingga',
        'kepada',
        'bersama',
        'se',
    ];

    /**
     * Detect whether a title is predominantly ALL CAPS and normalize it into clean Indonesian Title Case.
     */
    public static function normalizeIfAllCaps(string $title): string
    {
        $clean = trim((string) preg_replace('/[\s\xC2\xA0]+/u', ' ', $title));
        if ($clean === '') {
            return '';
        }

        if (! self::isAllCaps($clean)) {
            return $clean;
        }

        $words = preg_split('/\s+/u', $clean) ?: [];
        $result = [];
        $capitalizeNext = true;

        foreach ($words as $index => $word) {
            $transformed = self::normalizeWord($word, $index === 0 || $capitalizeNext);
            $result[] = $transformed;
            $capitalizeNext = preg_match('/[:\-–—\/(]$/u', $word) === 1;
        }

        return implode(' ', $result);
    }

    public static function isAllCaps(string $text): bool
    {
        preg_match_all('/\p{L}/u', $text, $letters);
        $total = count($letters[0]);
        if ($total < 8) {
            return false;
        }

        preg_match_all('/\p{Lu}/u', $text, $uppers);
        $upperCount = count($uppers[0]);

        return ($upperCount / $total) >= 0.85;
    }

    private static function normalizeWord(string $word, bool $forceCapitalize): string
    {
        if (preg_match('/^([^\p{L}\p{N}]*)(.*?)([^\p{L}\p{N}]*)$/u', $word, $m) !== 1) {
            return $word;
        }

        $prefix = $m[1];
        $core = $m[2];
        $suffix = $m[3];

        if ($core === '') {
            return $word;
        }

        $lower = mb_strtolower($core, 'UTF-8');

        // Preserve Hijri year markers like 1446H or numeric codes like 146/G/2024
        if (preg_match('/^\d{3,4}h$/i', $core) === 1) {
            return $prefix.strtoupper($core).$suffix;
        }

        // Preserve exact dictionary tokens (acronyms, Roman numerals, academic titles)
        if (isset(self::PRESERVED_TOKENS[$lower])) {
            // Disambiguate 'se' (Surat Edaran when uppercase/followed by number, or connector "se-")
            if ($lower === 'se' && ! $forceCapitalize) {
                return $prefix.'SE'.$suffix;
            }

            return $prefix.self::PRESERVED_TOKENS[$lower].$suffix;
        }

        // Handle slash-separated or hyphenated compound tokens (e.g., WBK/WBBM, SE-01, PA/PTA)
        if (str_contains($core, '/') || str_contains($core, '-')) {
            $parts = preg_split('/([\/\-])/u', $core, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$core];
            $rebuilt = '';
            foreach ($parts as $i => $part) {
                if ($part === '/' || $part === '-') {
                    $rebuilt .= $part;

                    continue;
                }
                $rebuilt .= self::normalizeWord($part, $i === 0 ? $forceCapitalize : true);
            }

            return $prefix.$rebuilt.$suffix;
        }

        // Lowercase short Indonesian prepositions/conjunctions unless starting a clause
        if (! $forceCapitalize && in_array($lower, self::LOWERCASE_CONNECTORS, true)) {
            return $prefix.$lower.$suffix;
        }

        // Standard Title Case for normal words
        $firstChar = mb_strtoupper(mb_substr($lower, 0, 1, 'UTF-8'), 'UTF-8');
        $rest = mb_substr($lower, 1, null, 'UTF-8');

        return $prefix.$firstChar.$rest.$suffix;
    }
}
