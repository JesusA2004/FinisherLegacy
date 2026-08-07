<?php

namespace Database\Factories;

use App\Enums\ResultStatus;
use App\Models\EventParticipant;
use App\Models\EventResult;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventResult>
 */
class EventResultFactory extends Factory
{
    protected $model = EventResult::class;

    public function definition(): array
    {
        $seconds = fake()->numberBetween(20 * 60, 6 * 3600);
        $time = gmdate($seconds >= 3600 ? 'H:i:s' : 'i:s', $seconds);
        $paceSeconds = fake()->numberBetween(240, 420);

        return [
            'event_participant_id' => EventParticipant::factory(),
            'official_time' => $time,
            'chip_time' => $time,
            'pace' => sprintf('%d:%02d', intdiv($paceSeconds, 60), $paceSeconds % 60),
            'overall_position' => fake()->numberBetween(1, 5000),
            'gender_position' => fake()->numberBetween(1, 2500),
            'category_position' => fake()->numberBetween(1, 500),
            'status' => ResultStatus::Finished,
            'result_source' => 'timing_import',
            'verified_at' => now(),
        ];
    }
}
