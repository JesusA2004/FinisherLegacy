<?php

namespace App\Services;

use App\Enums\MedalImageType;
use App\Enums\ResultStatus;
use App\Models\EventEdition;
use App\Models\EventParticipant;
use App\Models\EventRace;
use App\Models\Medal;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class MedalService
{
    public function __construct(private readonly MedalImageService $images) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data, UploadedFile $front, ?UploadedFile $back): Medal
    {
        return DB::transaction(function () use ($user, $data, $front, $back) {
            $attributes = $this->resolveOrigin($user, $data);
            $attributes['story'] = $data['story'] ?? null;
            $attributes['visibility'] = $data['visibility'];
            $attributes['status'] = 'active';

            /** @var Medal $medal */
            $medal = $user->medals()->create($attributes);

            $this->images->store($front, $medal, MedalImageType::Front, 0);

            if ($back) {
                $this->images->store($back, $medal, MedalImageType::Back, 1);
            }

            return $medal->fresh('images');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Medal $medal, array $data, ?UploadedFile $front, ?UploadedFile $back): Medal
    {
        return DB::transaction(function () use ($medal, $data, $front, $back) {
            $isOfficial = $medal->event_participant_id !== null;

            $attributes = [
                'story' => $data['story'] ?? null,
                'visibility' => $data['visibility'],
            ];

            if (! $isOfficial) {
                foreach (['event_date', 'city', 'country', 'distance_label', 'official_time', 'pace'] as $field) {
                    if (array_key_exists($field, $data)) {
                        $attributes[$field] = $data[$field];
                    }
                }

                if (! empty($data['event_name_manual'])) {
                    $attributes['event_name_manual'] = $data['event_name_manual'];
                    $attributes['title'] = $data['event_name_manual'];
                }
            }

            $medal->update($attributes);

            if ($front) {
                $this->replaceImage($medal, MedalImageType::Front, $front, 0);
            }

            if ($back) {
                $this->replaceImage($medal, MedalImageType::Back, $back, 1);
            }

            return $medal->fresh('images');
        });
    }

    public function delete(Medal $medal): void
    {
        // Medal uses SoftDeletes: this always archives (deleted_at is set) and
        // never destroys the row, so Plate/LegacyCode historical links stay intact.
        $medal->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function resolveOrigin(User $user, array $data): array
    {
        if ($data['origin'] !== 'registered') {
            return [
                'event_id' => null,
                'event_edition_id' => null,
                'event_race_id' => null,
                'event_participant_id' => null,
                'title' => $data['event_name_manual'],
                'event_name_manual' => $data['event_name_manual'],
                'event_date' => $data['event_date'] ?? null,
                'distance_label' => $data['distance_label'] ?? null,
                'official_time' => $data['official_time'] ?? null,
                'pace' => $data['pace'] ?? null,
                'city' => $data['city'] ?? null,
                'country' => $data['country'] ?? null,
            ];
        }

        $edition = EventEdition::with('event')->findOrFail((int) $data['event_edition_id']);
        $race = EventRace::findOrFail((int) $data['event_race_id']);

        $match = $this->matchParticipant($user, (int) $data['event_race_id']);

        return [
            'event_id' => $edition->event_id,
            'event_edition_id' => $edition->id,
            'event_race_id' => $race->id,
            'event_participant_id' => $match?->id,
            'title' => $edition->event->name,
            'event_name_manual' => null,
            'event_date' => $edition->event_date->toDateString(),
            'distance_label' => $race->name,
            'official_time' => $match?->result?->official_time,
            'pace' => $match?->result?->pace,
            'city' => $edition->city,
            'country' => $edition->country,
        ];
    }

    public function matchParticipant(User $user, int $eventRaceId): ?EventParticipant
    {
        $participant = EventParticipant::query()
            ->where('event_race_id', $eventRaceId)
            ->where('user_id', $user->id)
            ->with('result')
            ->first();

        if (! $participant?->result) {
            return null;
        }

        $verifiedStatuses = [ResultStatus::Finished, ResultStatus::Verified];

        return in_array($participant->result->status, $verifiedStatuses, true) ? $participant : null;
    }

    private function replaceImage(Medal $medal, MedalImageType $type, UploadedFile $file, int $sortOrder): void
    {
        $existing = $medal->images()->where('type', $type)->first();

        if ($existing) {
            $this->images->delete($existing);
        }

        $this->images->store($file, $medal, $type, $sortOrder);
    }
}
