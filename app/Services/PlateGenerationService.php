<?php

namespace App\Services;

use App\Enums\LegacyCodeStatus;
use App\Enums\PlateGenerationMode;
use App\Enums\PlateStatus;
use App\Enums\ProductionJobStatus;
use App\Models\EventEdition;
use App\Models\EventParticipant;
use App\Models\LegacyCode;
use App\Models\Plate;
use App\Models\PlateTemplate;
use App\Models\ProductionJob;
use App\Support\CodeGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The only place a Plate is ever created — both Event OS "integrated" (has
 * a participant/result) and "quick" (no participant yet) paths funnel
 * through here, so a Legacy Code + QR + ProductionJob are always created
 * together with the plate, never as an afterthought.
 */
class PlateGenerationService
{
    public function generateIntegrated(EventParticipant $participant, ?PlateTemplate $template = null): Plate
    {
        $participant->loadMissing(['eventEdition.event', 'eventRace', 'result']);
        $edition = $participant->eventEdition;
        $result = $participant->result;

        return DB::transaction(function () use ($participant, $edition, $result, $template) {
            $plate = Plate::create([
                'user_id' => $participant->user_id,
                'event_edition_id' => $edition->id,
                'event_participant_id' => $participant->id,
                'plate_template_id' => $template?->id,
                'serial_number' => CodeGenerator::generate('PLT', 8),
                'generation_mode' => PlateGenerationMode::Integrated,
                'athlete_name' => $participant->full_name ?: trim("{$participant->first_name} {$participant->last_name}"),
                'bib_number' => $participant->bib_number,
                'event_name' => $edition->event->name,
                'race_name' => $participant->eventRace->name,
                'official_time' => $result?->official_time,
                'pace' => $result?->pace,
                'event_date' => $edition->event_date,
                'status' => PlateStatus::Draft,
                'linked_at' => $participant->user_id ? now() : null,
            ]);

            $this->attachLegacyCode($plate, $participant->user_id);
            $this->queueProduction($plate);

            return $plate->fresh(['legacyCode', 'latestProductionJob']);
        });
    }

    /**
     * @param  array{athlete_name: string, bib_number?: ?string, event_name?: ?string, race_name?: ?string, official_time?: ?string, pace?: ?string}  $data
     */
    public function generateQuick(EventEdition $edition, array $data, ?PlateTemplate $template = null): Plate
    {
        $edition->loadMissing('event');

        return DB::transaction(function () use ($edition, $data, $template) {
            $plate = Plate::create([
                'user_id' => null,
                'event_edition_id' => $edition->id,
                'plate_template_id' => $template?->id,
                'serial_number' => CodeGenerator::generate('PLT', 8),
                'generation_mode' => PlateGenerationMode::Quick,
                'athlete_name' => $data['athlete_name'],
                'bib_number' => $data['bib_number'] ?? null,
                'event_name' => $data['event_name'] ?? $edition->event->name,
                'race_name' => $data['race_name'] ?? null,
                'official_time' => $data['official_time'] ?? null,
                'pace' => $data['pace'] ?? null,
                'event_date' => $edition->event_date,
                'status' => PlateStatus::Draft,
            ]);

            $this->attachLegacyCode($plate, null);
            $this->queueProduction($plate);

            return $plate->fresh(['legacyCode', 'latestProductionJob']);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function previewPayload(Plate $plate): array
    {
        $plate->loadMissing(['legacyCode', 'plateTemplate']);

        return [
            'serial_number' => $plate->serial_number,
            'athlete_name' => $plate->athlete_name,
            'bib_number' => $plate->bib_number,
            'event_name' => $plate->event_name,
            'race_name' => $plate->race_name,
            'official_time' => $plate->official_time,
            'pace' => $plate->pace,
            'event_date' => $plate->event_date?->toDateString(),
            'status' => $plate->status->value,
            'legacy_code' => $plate->legacyCode?->code,
            'qr_url' => $plate->legacyCode ? route('legacy-code.qr', $plate->legacyCode->code) : null,
            'template' => $plate->plateTemplate?->configuration,
        ];
    }

    private function attachLegacyCode(Plate $plate, ?int $userId): void
    {
        $legacyCode = LegacyCode::create([
            'code' => CodeGenerator::unique('FL', fn (string $c) => LegacyCode::query()->where('code', $c)->exists()),
            'uuid' => Str::uuid(),
            'plate_id' => $plate->id,
            'user_id' => $userId,
            'status' => LegacyCodeStatus::Assigned,
            'assigned_at' => now(),
        ]);

        $plate->update(['legacy_code_id' => $legacyCode->id]);
    }

    private function queueProduction(Plate $plate): void
    {
        ProductionJob::create([
            'plate_id' => $plate->id,
            'event_edition_id' => $plate->event_edition_id,
            'priority' => 0,
            'status' => ProductionJobStatus::Queued,
            'queued_at' => now(),
            'attempts' => 0,
        ]);

        $plate->update(['status' => PlateStatus::Queued]);
    }
}
