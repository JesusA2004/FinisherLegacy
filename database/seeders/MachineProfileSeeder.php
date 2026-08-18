<?php

namespace Database\Seeders;

use App\Models\MachineProfile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MachineProfileSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        MachineProfile::query()->updateOrCreate(
            ['name' => 'Fiber 30W — LightBurn'],
            [
                'type' => 'fiber',
                'software' => 'LightBurn',
                'default_format' => 'svg',
                'width_mm' => 60,
                'height_mm' => 40,
                'active' => true,
            ],
        );
    }
}
