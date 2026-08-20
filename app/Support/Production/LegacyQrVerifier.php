<?php

namespace App\Support\Production;

/**
 * Compares what a device/operator scanned against the Legacy Code a plate
 * actually expects. Accepts either the full public URL a real QR encodes
 * (`https://.../l/FL-Q8K2MX7P`) or a bare code, so it works the same
 * whether the scanner SDK hands back the raw decoded string or something
 * that already extracted the path.
 */
final class LegacyQrVerifier
{
    public function normalize(string $decoded): string
    {
        $decoded = trim($decoded);

        if (preg_match('~/l/([^/?#]+)~i', $decoded, $matches) === 1) {
            return strtoupper($matches[1]);
        }

        return strtoupper($decoded);
    }

    public function matches(string $decoded, string $expectedCode): bool
    {
        return $this->normalize($decoded) === strtoupper(trim($expectedCode));
    }
}
