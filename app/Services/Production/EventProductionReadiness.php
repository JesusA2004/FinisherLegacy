<?php

namespace App\Services\Production;

use App\Models\EventEdition;
use App\Models\EventProductionCheck;
use App\Models\ProductionDevice;
use App\Support\Production\ReadinessCheck;

/**
 * "Is this edition ready to produce plates" — one aggregate check instead
 * of scattering the same signals across Event Ops/production-setup pages
 * (docs/adr/0006-event-operations.md §10). Only the template check
 * blocks; everything else is informational — a station or QR calibration
 * missing still allows manual-mode production (§81 del prompt original).
 */
class EventProductionReadiness
{
    public function check(EventEdition $edition): ReadinessCheck
    {
        $edition->loadMissing(['event', 'races', 'productionCheck']);

        $hasParticipants = $edition->participants()->exists();
        $hasRace = $edition->races->isNotEmpty();
        $hasTemplate = $edition->defaultPlateTemplateVersion() !== null;
        $stations = ProductionDevice::query()->where('event_edition_id', $edition->id)->get();
        $hasStation = $stations->isNotEmpty();
        $hasMachineProfile = $stations->contains(fn (ProductionDevice $d) => $d->machine_profile_id !== null);
        $qrTested = ($edition->productionCheck ?? EventProductionCheck::where('event_edition_id', $edition->id)->first())?->qr_tested_at !== null;

        return new ReadinessCheck(
            ready: $hasTemplate,
            checks: [
                'event' => true,
                'data_source' => $hasParticipants,
                'race' => $hasRace,
                'template' => $hasTemplate,
                'station' => $hasStation,
                'machine_profile' => $hasMachineProfile,
                'qr_calibration' => $qrTested,
            ],
            blockingReasons: $hasTemplate ? [] : ['NO_TEMPLATE'],
        );
    }
}
