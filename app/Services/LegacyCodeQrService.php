<?php

namespace App\Services;

use App\Models\LegacyCode;

/**
 * The Legacy Code is the source of truth for a Plate's identity — the QR
 * image is purely derived from it and can be regenerated at any time from
 * the stable public URL. Nothing should ever treat the SVG file itself as
 * authoritative.
 */
class LegacyCodeQrService
{
    public function __construct(private readonly QrCodeService $qr) {}

    public function publicUrl(LegacyCode $legacyCode): string
    {
        return route('legacy-code.show', $legacyCode->code);
    }

    public function svg(LegacyCode $legacyCode, int $size = 320): string
    {
        return $this->qr->svg($this->publicUrl($legacyCode), $size);
    }
}
