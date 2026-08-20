<?php

namespace App\Support;

/**
 * Recorded on every `ProductionArtifact` so a frozen file can always be
 * traced back to the renderer logic that produced it. Bump this
 * deliberately whenever a change to `PlateTemplateRenderService` (or
 * anything it calls) would change physical output for existing published
 * versions — never tied to a git hash, which changes on every unrelated
 * commit and says nothing about whether output actually changed.
 */
final class PlateRendererVersion
{
    public const CURRENT = '1';
}
