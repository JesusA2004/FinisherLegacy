<?php

namespace Database\Seeders;

use App\Enums\PlateTemplateVersionStatus;
use App\Models\PlateTemplate;
use App\Models\PlateTemplateVersion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlateTemplateSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $template = PlateTemplate::query()->updateOrCreate(
            ['slug' => 'ironman-cozumel-acero-60x40'],
            [
                'name' => 'Ironman Cozumel — Acero 60x40 V1',
                'description' => 'Placa metálica 60×40mm, grabado láser, frente y reverso.',
                'width_mm' => 60,
                'height_mm' => 40,
                'material' => 'Acero inoxidable cepillado',
                'orientation' => 'landscape',
                'safe_margin_mm' => 3,
                'active' => true,
            ],
        );

        PlateTemplateVersion::query()->updateOrCreate(
            ['plate_template_id' => $template->id, 'version' => 1],
            [
                'front_configuration' => ['elements' => $this->frontElements()],
                'back_configuration' => ['elements' => $this->backElements()],
                'status' => PlateTemplateVersionStatus::Published,
            ],
        );
    }

    /** @return list<array<string, mixed>> */
    private function frontElements(): array
    {
        return [
            ['id' => 'brand', 'type' => 'static_text', 'text' => 'FINISHER LEGACY', 'x_mm' => 3, 'y_mm' => 3.2, 'width_mm' => 40, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 6, 'font_weight' => 700, 'text_align' => 'left', 'color' => '#0a090c'],
            ['id' => 'event_name', 'type' => 'dynamic_text', 'field' => 'event_name', 'x_mm' => 3, 'y_mm' => 6, 'width_mm' => 54, 'height_mm' => 4, 'font_family' => 'Inter', 'font_size_pt' => 5, 'font_weight' => 600, 'text_align' => 'left', 'color' => '#0a090c', 'auto_fit' => true, 'min_font_size_pt' => 3.5],
            ['id' => 'event_date', 'type' => 'dynamic_text', 'field' => 'event_date', 'x_mm' => 3, 'y_mm' => 10, 'width_mm' => 54, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 3.5, 'font_weight' => 400, 'text_align' => 'left', 'color' => '#4a4a4a'],
            ['id' => 'athlete_name', 'type' => 'dynamic_text', 'field' => 'athlete_name', 'required' => true, 'x_mm' => 3, 'y_mm' => 14.5, 'width_mm' => 40, 'height_mm' => 6, 'font_family' => 'Inter', 'font_size_pt' => 9, 'font_weight' => 700, 'text_align' => 'left', 'color' => '#0a090c', 'auto_fit' => true, 'min_font_size_pt' => 5],
            ['id' => 'official_time', 'type' => 'dynamic_text', 'field' => 'official_time', 'x_mm' => 3, 'y_mm' => 21.5, 'width_mm' => 30, 'height_mm' => 6, 'font_family' => 'Inter', 'font_size_pt' => 8.5, 'font_weight' => 700, 'text_align' => 'left', 'color' => '#0a090c'],
            ['id' => 'swim', 'type' => 'static_text', 'text' => 'SWIM {{swim_time}}', 'x_mm' => 3, 'y_mm' => 29, 'width_mm' => 18, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 3.2, 'font_weight' => 400, 'text_align' => 'left', 'color' => '#4a4a4a'],
            ['id' => 'bike', 'type' => 'static_text', 'text' => 'BIKE {{bike_time}}', 'x_mm' => 22, 'y_mm' => 29, 'width_mm' => 18, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 3.2, 'font_weight' => 400, 'text_align' => 'left', 'color' => '#4a4a4a'],
            ['id' => 'run', 'type' => 'static_text', 'text' => 'RUN {{run_time}}', 'x_mm' => 3, 'y_mm' => 32.5, 'width_mm' => 18, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 3.2, 'font_weight' => 400, 'text_align' => 'left', 'color' => '#4a4a4a'],
            ['id' => 'category', 'type' => 'static_text', 'text' => '{{category}} · {{category_position}}', 'x_mm' => 22, 'y_mm' => 32.5, 'width_mm' => 25, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 3.2, 'font_weight' => 400, 'text_align' => 'left', 'color' => '#4a4a4a'],
            ['id' => 'bib', 'type' => 'dynamic_text', 'field' => 'bib_number', 'text' => '#{{bib_number}}', 'x_mm' => 45, 'y_mm' => 3.2, 'width_mm' => 12, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 3.5, 'font_weight' => 400, 'text_align' => 'right', 'color' => '#4a4a4a'],
            ['id' => 'qr', 'type' => 'qr', 'x_mm' => 46, 'y_mm' => 26, 'width_mm' => 11, 'height_mm' => 11, 'error_correction' => 'H'],
            ['id' => 'serial', 'type' => 'serial', 'text' => '{{plate_serial}}', 'x_mm' => 3, 'y_mm' => 34.2, 'width_mm' => 30, 'height_mm' => 2.5, 'font_family' => 'Inter', 'font_size_pt' => 2.6, 'font_weight' => 400, 'text_align' => 'left', 'color' => '#8a8a8a'],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function backElements(): array
    {
        return [
            ['id' => 'phrase', 'type' => 'dynamic_text', 'field' => 'personal_phrase', 'text' => '{{personal_phrase}}', 'x_mm' => 6, 'y_mm' => 12, 'width_mm' => 48, 'height_mm' => 14, 'font_family' => 'Inter', 'font_size_pt' => 5, 'font_weight' => 400, 'text_align' => 'center', 'color' => '#0a090c', 'auto_fit' => true, 'min_font_size_pt' => 3.5],
            ['id' => 'fl_mark', 'type' => 'static_text', 'text' => 'FL', 'x_mm' => 27, 'y_mm' => 27, 'width_mm' => 6, 'height_mm' => 5, 'font_family' => 'Inter', 'font_size_pt' => 7, 'font_weight' => 700, 'text_align' => 'center', 'color' => '#0a090c'],
            ['id' => 'brand_back', 'type' => 'static_text', 'text' => 'FINISHER LEGACY', 'x_mm' => 3, 'y_mm' => 33.8, 'width_mm' => 40, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 3.2, 'font_weight' => 600, 'text_align' => 'left', 'color' => '#4a4a4a'],
            ['id' => 'serial_back', 'type' => 'serial', 'text' => '{{plate_serial}}', 'x_mm' => 45, 'y_mm' => 33.8, 'width_mm' => 12, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 2.6, 'font_weight' => 400, 'text_align' => 'right', 'color' => '#8a8a8a'],
        ];
    }
}
