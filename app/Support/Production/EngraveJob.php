<?php

namespace App\Support\Production;

/**
 * What a driver needs to engrave one face — deliberately just an artifact
 * reference + face, never laser power/frequency/speed (docs/adr/0007
 * §Safety boundary: those live in a locally-calibrated machine profile,
 * the backend never dictates them).
 */
final class EngraveJob
{
    public function __construct(
        public readonly int $productionJobId,
        public readonly string $face,
        public readonly string $artifactPath,
    ) {}
}
