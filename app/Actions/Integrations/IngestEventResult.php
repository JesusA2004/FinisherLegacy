<?php

namespace App\Actions\Integrations;

use App\Enums\ResultStatus;
use App\Models\EventParticipant;
use App\Models\EventResult;
use App\Support\Integrations\ExternalResultData;
use Illuminate\Support\Facades\DB;

/**
 * Upsert one EventResult + its splits, from any source (mock/real
 * provider, CSV) — the single place that writes to `event_results` from
 * an ingestion pipeline, mirroring App\Actions\Athletes\IngestEventParticipant
 * (docs/adr/0005-unified-event-ingestion.md §Ingest actions). Never creates
 * an Athlete or EventParticipant — the participant must already exist.
 *
 * Idempotent by `event_participant_id` (unique) for the result row and by
 * `sequence` for splits — the same payload received twenty times still
 * produces one EventResult and one split per sequence.
 *
 * Locked fields (`event_results.manual_override_fields`) are the one
 * exception: once an admin corrects a field, this action skips it forever,
 * no matter what the provider sends next (docs/adr/0005 §97-99). Producing
 * a Plate later snapshots whatever is on the row at that instant — this
 * action never reaches into an already-generated Plate.
 */
class IngestEventResult
{
    public function handle(EventParticipant $participant, ExternalResultData $data, string $resultSource): EventResult
    {
        return DB::transaction(function () use ($participant, $data, $resultSource) {
            $result = EventResult::firstOrNew(['event_participant_id' => $participant->id]);
            $isExisting = $result->exists;

            $locked = fn (string $field): bool => $isExisting && $result->hasLockedField($field);

            if ($data->officialTime !== null && ! $locked('official_time')) {
                $result->official_time = $data->officialTime;
            }
            if ($data->chipTime !== null && ! $locked('chip_time')) {
                $result->chip_time = $data->chipTime;
            }
            if ($data->pace !== null && ! $locked('pace')) {
                $result->pace = $data->pace;
            }
            if ($data->overallPosition !== null && ! $locked('overall_position')) {
                $result->overall_position = max(0, $data->overallPosition);
            }
            if ($data->genderPosition !== null && ! $locked('gender_position')) {
                $result->gender_position = max(0, $data->genderPosition);
            }
            if ($data->categoryPosition !== null && ! $locked('category_position')) {
                $result->category_position = max(0, $data->categoryPosition);
            }

            $status = $data->status !== null ? ResultStatus::tryFrom($data->status) : null;
            if ($status !== null && ! ($isExisting && $result->hasLockedField('status'))) {
                $result->status = $status;
            }

            $result->result_source = $resultSource;
            $result->save();

            foreach ($data->splits as $split) {
                $result->splits()->updateOrCreate(
                    ['sequence' => $split->sequence],
                    [
                        'type' => $split->type,
                        'label' => $split->label,
                        'distance_value' => $split->distanceValue,
                        'distance_unit' => $split->distanceUnit,
                        'segment_time' => $split->segmentTime,
                        'elapsed_time' => $split->elapsedTime,
                        'pace' => $split->pace,
                    ],
                );
            }

            // `load()`, not `fresh()` — fresh() would re-fetch a brand new
            // model instance and lose `wasRecentlyCreated`, which
            // App\Actions\Integrations\SyncExternalResults relies on to
            // count a result as created vs. updated.
            return $result->load('splits');
        });
    }
}
