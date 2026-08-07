<?php

namespace Database\Seeders;

use App\Models\Sport;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SportSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        foreach ([
            'Running', 'Trail', 'Triatlón', 'Ciclismo', 'Natación', 'Obstacle Racing', 'Otros',
        ] as $index => $name) {
            Sport::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'active' => true, 'sort_order' => $index],
            );
        }
    }
}
