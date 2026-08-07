<?php

namespace Database\Seeders;

use App\Models\PlateTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlateTemplateSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        PlateTemplate::query()->updateOrCreate(
            ['slug' => 'classic-black-gold'],
            [
                'name' => 'Classic Black & Gold',
                'width' => 1500,
                'height' => 2100,
                'configuration' => [
                    'background' => '#0b0b0c',
                    'fields' => [
                        'athlete_name' => ['x' => 120, 'y' => 260, 'font_size' => 64, 'color' => '#f5d67b', 'align' => 'left'],
                        'event_name' => ['x' => 120, 'y' => 360, 'font_size' => 36, 'color' => '#ffffff', 'align' => 'left'],
                        'race' => ['x' => 120, 'y' => 420, 'font_size' => 28, 'color' => '#c9c9c9', 'align' => 'left'],
                        'time' => ['x' => 120, 'y' => 520, 'font_size' => 48, 'color' => '#ffffff', 'align' => 'left'],
                        'pace' => ['x' => 120, 'y' => 580, 'font_size' => 28, 'color' => '#c9c9c9', 'align' => 'left'],
                        'qr' => ['x' => 1180, 'y' => 1780, 'size' => 220],
                    ],
                ],
                'active' => true,
            ],
        );
    }
}
