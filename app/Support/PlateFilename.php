<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Filenames operators actually see on a shop-floor PC — must stay readable
 * and never leak PII beyond a first name (no email, no full contact info).
 */
class PlateFilename
{
    /**
     * "María Cármen Alániz" -> "MARIA-CARMEN-ALANIZ"
     *
     * Uses Str::ascii() (Symfony's curated transliteration table) rather
     * than iconv('...//TRANSLIT...'): iconv's transliteration depends on the
     * system's ICU/glibc locale data, which is inconsistent across
     * platforms (observed producing stray apostrophes for accented
     * characters on Windows) — Str::ascii() gives the same result everywhere.
     */
    public static function sanitize(string $value): string
    {
        $ascii = Str::ascii($value);
        $slug = preg_replace('/[^A-Za-z0-9]+/', '-', $ascii) ?? '';

        return strtoupper(trim($slug, '-')) ?: 'PLACA';
    }

    /**
     * e.g. "001242_ZURIEL-AVILA" — bib when we have one (what's stamped on
     * the bib matches what's on the plate), the serial otherwise.
     */
    public static function batchPrefix(string $athleteName, ?string $bibNumber, string $serial): string
    {
        $id = $bibNumber !== null && $bibNumber !== '' ? $bibNumber : $serial;

        return self::sanitize($id).'_'.self::sanitize($athleteName);
    }
}
