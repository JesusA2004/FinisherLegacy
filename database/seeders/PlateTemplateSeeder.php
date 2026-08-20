<?php

namespace Database\Seeders;

use App\Enums\PlateSportType;
use App\Enums\PlateTemplateVersionStatus;
use App\Models\PlateTemplate;
use App\Models\PlateTemplateVersion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Seeds the three official presets: Running, Triathlon, Cycling — all
 * 60x40mm steel, all following the two-face rule (see docs/plate-production.md):
 * FRONT carries the sport data uncluttered, BACK carries a large scannable QR
 * + Legacy Code + Finisher Legacy branding. No generic preset references a
 * real event/brand logo — only Finisher Legacy's own wordmark and simple
 * vector iconography, per the "no protected marks" rule.
 */
class PlateTemplateSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->seedPreset(
            slug: 'triathlon-premium-60x40',
            name: 'Triathlon Premium — Acero 60x40',
            description: 'Placa metálica 60×40mm para triatlón: frente con splits de natación/ciclismo/carrera, reverso con QR grande y Legacy Code.',
            sportType: PlateSportType::Triathlon,
            front: $this->triathlonFront(),
            back: $this->triathlonBack(),
        );

        $this->seedPreset(
            slug: 'running-classic-60x40',
            name: 'Running Classic — Acero 60x40',
            description: 'Placa metálica 60×40mm para carreras de ruta: frente con distancia, tiempo y ritmo, reverso con QR grande y Legacy Code.',
            sportType: PlateSportType::Running,
            front: $this->runningFront(),
            back: $this->runningBack(),
        );

        $this->seedPreset(
            slug: 'cycling-classic-60x40',
            name: 'Cycling Classic — Acero 60x40',
            description: 'Placa metálica 60×40mm para ciclismo: frente con distancia, tiempo y ritmo, reverso con QR grande y Legacy Code.',
            sportType: PlateSportType::Cycling,
            front: $this->cyclingFront(),
            back: $this->cyclingBack(),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $front
     * @param  list<array<string, mixed>>  $back
     */
    private function seedPreset(string $slug, string $name, string $description, PlateSportType $sportType, array $front, array $back): void
    {
        $template = PlateTemplate::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'description' => $description,
                'width_mm' => 60,
                'height_mm' => 40,
                'material' => 'Acero inoxidable cepillado',
                'orientation' => 'landscape',
                'safe_margin_mm' => 3,
                'sport_type' => $sportType->value,
                'active' => true,
            ],
        );

        PlateTemplateVersion::query()->updateOrCreate(
            ['plate_template_id' => $template->id, 'version' => 1],
            [
                'front_configuration' => ['elements' => $front],
                'back_configuration' => ['elements' => $back],
                'status' => PlateTemplateVersionStatus::Published,
            ],
        );
    }

    /**
     * Shared brand mark, top-left on every front face.
     *
     * @return array<string, mixed>
     */
    private function brandMark(): array
    {
        return ['id' => 'brand', 'type' => 'static_text', 'text' => 'FINISHER LEGACY', 'x_mm' => 3, 'y_mm' => 3, 'width_mm' => 40, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 6, 'font_weight' => 700, 'text_align' => 'left', 'color' => '#0a090c'];
    }

    /**
     * Shared back face skeleton: brand, big QR, Legacy Code, tagline — the
     * "Legacy" face every preset shares. All boxes sit within the 3mm safe
     * margin (plate is 60x40mm) even though text is center-aligned — the
     * renderer's margin check is bounding-box based, not glyph-aware, so the
     * declared box itself must respect the margin.
     *
     * @return list<array<string, mixed>>
     */
    private function backSkeleton(): array
    {
        return [
            ['id' => 'brand_back', 'type' => 'static_text', 'text' => 'FINISHER LEGACY', 'x_mm' => 3, 'y_mm' => 3, 'width_mm' => 54, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 6.5, 'font_weight' => 700, 'text_align' => 'center', 'color' => '#0a090c'],
            // Generous, uncontested QR area — back has no competing data, so
            // the code that actually gets scanned in the field wins the space.
            ['id' => 'qr', 'type' => 'qr', 'x_mm' => 21, 'y_mm' => 9.3, 'width_mm' => 18, 'height_mm' => 18, 'error_correction' => 'H'],
            ['id' => 'legacy_code', 'type' => 'dynamic_text', 'field' => 'legacy_code', 'x_mm' => 3, 'y_mm' => 28, 'width_mm' => 54, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 6, 'font_weight' => 700, 'text_align' => 'center', 'color' => '#0a090c'],
            ['id' => 'tagline', 'type' => 'static_text', 'text' => 'TU HISTORIA. TU LEGADO.', 'x_mm' => 3, 'y_mm' => 31.5, 'width_mm' => 54, 'height_mm' => 2.2, 'font_family' => 'Inter', 'font_size_pt' => 4, 'font_weight' => 400, 'text_align' => 'center', 'color' => '#4a4a4a'],
            ['id' => 'phrase', 'type' => 'dynamic_text', 'field' => 'personal_phrase', 'visible_when' => 'personal_phrase', 'text' => '{{personal_phrase}}', 'x_mm' => 6, 'y_mm' => 34.2, 'width_mm' => 48, 'height_mm' => 2.3, 'font_family' => 'Inter', 'font_size_pt' => 2.6, 'font_weight' => 400, 'text_align' => 'center', 'color' => '#8a8a8a', 'auto_fit' => true, 'min_font_size_pt' => 2],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function triathlonFront(): array
    {
        return [
            $this->brandMark(),
            ['id' => 'event_name', 'type' => 'dynamic_text', 'field' => 'event_name', 'x_mm' => 3, 'y_mm' => 6.5, 'width_mm' => 54, 'height_mm' => 4, 'font_family' => 'Inter', 'font_size_pt' => 5, 'font_weight' => 600, 'text_align' => 'left', 'color' => '#0a090c', 'auto_fit' => true, 'min_font_size_pt' => 3.5],
            ['id' => 'event_date', 'type' => 'dynamic_text', 'field' => 'event_date', 'x_mm' => 3, 'y_mm' => 10.5, 'width_mm' => 54, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 3.5, 'font_weight' => 400, 'text_align' => 'left', 'color' => '#4a4a4a'],
            ['id' => 'athlete_name', 'type' => 'dynamic_text', 'field' => 'athlete_name', 'required' => true, 'x_mm' => 3, 'y_mm' => 14.8, 'width_mm' => 54, 'height_mm' => 5.5, 'font_family' => 'Inter', 'font_size_pt' => 8.5, 'font_weight' => 700, 'text_align' => 'left', 'color' => '#0a090c', 'auto_fit' => true, 'min_font_size_pt' => 5],
            ['id' => 'official_time', 'type' => 'dynamic_text', 'field' => 'official_time', 'x_mm' => 3, 'y_mm' => 21, 'width_mm' => 32, 'height_mm' => 6.5, 'font_family' => 'Inter', 'font_size_pt' => 9, 'font_weight' => 700, 'text_align' => 'left', 'color' => '#0a090c'],
            ['id' => 'swim', 'type' => 'static_text', 'text' => 'SWIM {{swim_time}}', 'visible_when' => 'swim_time', 'x_mm' => 3, 'y_mm' => 28.5, 'width_mm' => 18, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 3.2, 'font_weight' => 400, 'text_align' => 'left', 'color' => '#4a4a4a'],
            ['id' => 'bike', 'type' => 'static_text', 'text' => 'BIKE {{bike_time}}', 'visible_when' => 'bike_time', 'x_mm' => 21, 'y_mm' => 28.5, 'width_mm' => 18, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 3.2, 'font_weight' => 400, 'text_align' => 'left', 'color' => '#4a4a4a'],
            ['id' => 'run', 'type' => 'static_text', 'text' => 'RUN {{run_time}}', 'visible_when' => 'run_time', 'x_mm' => 39, 'y_mm' => 28.5, 'width_mm' => 18, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 3.2, 'font_weight' => 400, 'text_align' => 'left', 'color' => '#4a4a4a'],
            ['id' => 'category', 'type' => 'static_text', 'text' => 'AGE GROUP {{category}}', 'visible_when' => 'category', 'x_mm' => 3, 'y_mm' => 32, 'width_mm' => 30, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 3.2, 'font_weight' => 400, 'text_align' => 'left', 'color' => '#4a4a4a'],
            ['id' => 'overall_position', 'type' => 'static_text', 'text' => 'GENERAL {{overall_position}}', 'visible_when' => 'overall_position', 'x_mm' => 33, 'y_mm' => 32, 'width_mm' => 24, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 3.2, 'font_weight' => 400, 'text_align' => 'left', 'color' => '#4a4a4a'],
            ['id' => 'bib', 'type' => 'dynamic_text', 'field' => 'bib_number', 'visible_when' => 'bib_number', 'text' => '#{{bib_number}}', 'x_mm' => 45, 'y_mm' => 3, 'width_mm' => 12, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 3.5, 'font_weight' => 400, 'text_align' => 'right', 'color' => '#4a4a4a'],
            ['id' => 'serial', 'type' => 'serial', 'text' => '{{plate_serial}}', 'x_mm' => 3, 'y_mm' => 34.5, 'width_mm' => 40, 'height_mm' => 2.2, 'font_family' => 'Inter', 'font_size_pt' => 2.6, 'font_weight' => 400, 'text_align' => 'left', 'color' => '#8a8a8a'],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function triathlonBack(): array
    {
        return [
            ...$this->backSkeleton(),
            // Thematic vector icons, part of the molde design itself — not
            // generated per plate. Sit in the margins beside the QR, the
            // only free real estate left on a 60x40mm back face.
            ['id' => 'icon_swim', 'type' => 'vector_icon', 'icon' => 'swimming', 'x_mm' => 5, 'y_mm' => 10.3, 'width_mm' => 8, 'height_mm' => 8, 'stroke' => '#8a7530', 'stroke_width_mm' => 0.35],
            ['id' => 'icon_bike', 'type' => 'vector_icon', 'icon' => 'cycling', 'x_mm' => 5, 'y_mm' => 19.3, 'width_mm' => 8, 'height_mm' => 8, 'stroke' => '#8a7530', 'stroke_width_mm' => 0.35],
            ['id' => 'icon_run', 'type' => 'vector_icon', 'icon' => 'running', 'x_mm' => 47, 'y_mm' => 14.8, 'width_mm' => 8, 'height_mm' => 8, 'stroke' => '#8a7530', 'stroke_width_mm' => 0.35],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function runningFront(): array
    {
        return [
            $this->brandMark(),
            ['id' => 'event_name', 'type' => 'dynamic_text', 'field' => 'event_name', 'x_mm' => 3, 'y_mm' => 6.5, 'width_mm' => 54, 'height_mm' => 4, 'font_family' => 'Inter', 'font_size_pt' => 5, 'font_weight' => 600, 'text_align' => 'left', 'color' => '#0a090c', 'auto_fit' => true, 'min_font_size_pt' => 3.5],
            ['id' => 'event_date', 'type' => 'dynamic_text', 'field' => 'event_date', 'x_mm' => 3, 'y_mm' => 10.5, 'width_mm' => 54, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 3.5, 'font_weight' => 400, 'text_align' => 'left', 'color' => '#4a4a4a'],
            ['id' => 'athlete_name', 'type' => 'dynamic_text', 'field' => 'athlete_name', 'required' => true, 'x_mm' => 3, 'y_mm' => 15, 'width_mm' => 54, 'height_mm' => 6, 'font_family' => 'Inter', 'font_size_pt' => 9, 'font_weight' => 700, 'text_align' => 'left', 'color' => '#0a090c', 'auto_fit' => true, 'min_font_size_pt' => 5],
            ['id' => 'official_time', 'type' => 'dynamic_text', 'field' => 'official_time', 'x_mm' => 3, 'y_mm' => 22, 'width_mm' => 54, 'height_mm' => 8, 'font_family' => 'Inter', 'font_size_pt' => 11, 'font_weight' => 700, 'text_align' => 'left', 'color' => '#0a090c'],
            ['id' => 'distance', 'type' => 'dynamic_text', 'field' => 'distance', 'visible_when' => 'distance', 'x_mm' => 3, 'y_mm' => 29.5, 'width_mm' => 20, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 3.4, 'font_weight' => 600, 'text_align' => 'left', 'color' => '#4a4a4a'],
            ['id' => 'pace', 'type' => 'static_text', 'text' => 'RITMO {{pace}}', 'visible_when' => 'pace', 'x_mm' => 23, 'y_mm' => 29.5, 'width_mm' => 20, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 3.2, 'font_weight' => 400, 'text_align' => 'left', 'color' => '#4a4a4a'],
            ['id' => 'overall_position', 'type' => 'static_text', 'text' => 'GENERAL {{overall_position}}', 'visible_when' => 'overall_position', 'x_mm' => 3, 'y_mm' => 32.5, 'width_mm' => 24, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 3.2, 'font_weight' => 400, 'text_align' => 'left', 'color' => '#4a4a4a'],
            ['id' => 'category', 'type' => 'static_text', 'text' => 'CAT {{category}}', 'visible_when' => 'category', 'x_mm' => 27, 'y_mm' => 32.5, 'width_mm' => 27, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 3.2, 'font_weight' => 400, 'text_align' => 'left', 'color' => '#4a4a4a'],
            ['id' => 'bib', 'type' => 'dynamic_text', 'field' => 'bib_number', 'visible_when' => 'bib_number', 'text' => '#{{bib_number}}', 'x_mm' => 45, 'y_mm' => 3, 'width_mm' => 12, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 3.5, 'font_weight' => 400, 'text_align' => 'right', 'color' => '#4a4a4a'],
            ['id' => 'serial', 'type' => 'serial', 'text' => '{{plate_serial}}', 'x_mm' => 3, 'y_mm' => 34.5, 'width_mm' => 40, 'height_mm' => 2.2, 'font_family' => 'Inter', 'font_size_pt' => 2.6, 'font_weight' => 400, 'text_align' => 'left', 'color' => '#8a8a8a'],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function runningBack(): array
    {
        return [
            ...$this->backSkeleton(),
            ['id' => 'icon_running', 'type' => 'vector_icon', 'icon' => 'running', 'x_mm' => 5, 'y_mm' => 13.3, 'width_mm' => 11, 'height_mm' => 11, 'stroke' => '#8a7530', 'stroke_width_mm' => 0.4],
            ['id' => 'icon_finish', 'type' => 'vector_icon', 'icon' => 'finish', 'x_mm' => 44, 'y_mm' => 13.3, 'width_mm' => 11, 'height_mm' => 11, 'stroke' => '#8a7530', 'stroke_width_mm' => 0.4],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function cyclingFront(): array
    {
        return [
            $this->brandMark(),
            ['id' => 'event_name', 'type' => 'dynamic_text', 'field' => 'event_name', 'x_mm' => 3, 'y_mm' => 6.5, 'width_mm' => 54, 'height_mm' => 4, 'font_family' => 'Inter', 'font_size_pt' => 5, 'font_weight' => 600, 'text_align' => 'left', 'color' => '#0a090c', 'auto_fit' => true, 'min_font_size_pt' => 3.5],
            ['id' => 'event_date', 'type' => 'dynamic_text', 'field' => 'event_date', 'x_mm' => 3, 'y_mm' => 10.5, 'width_mm' => 54, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 3.5, 'font_weight' => 400, 'text_align' => 'left', 'color' => '#4a4a4a'],
            ['id' => 'athlete_name', 'type' => 'dynamic_text', 'field' => 'athlete_name', 'required' => true, 'x_mm' => 3, 'y_mm' => 15, 'width_mm' => 54, 'height_mm' => 6, 'font_family' => 'Inter', 'font_size_pt' => 9, 'font_weight' => 700, 'text_align' => 'left', 'color' => '#0a090c', 'auto_fit' => true, 'min_font_size_pt' => 5],
            ['id' => 'official_time', 'type' => 'dynamic_text', 'field' => 'official_time', 'x_mm' => 3, 'y_mm' => 22, 'width_mm' => 54, 'height_mm' => 8, 'font_family' => 'Inter', 'font_size_pt' => 11, 'font_weight' => 700, 'text_align' => 'left', 'color' => '#0a090c'],
            ['id' => 'distance', 'type' => 'dynamic_text', 'field' => 'distance', 'visible_when' => 'distance', 'x_mm' => 3, 'y_mm' => 29.5, 'width_mm' => 20, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 3.4, 'font_weight' => 600, 'text_align' => 'left', 'color' => '#4a4a4a'],
            ['id' => 'pace', 'type' => 'static_text', 'text' => 'VEL. {{pace}}', 'visible_when' => 'pace', 'x_mm' => 23, 'y_mm' => 29.5, 'width_mm' => 20, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 3.2, 'font_weight' => 400, 'text_align' => 'left', 'color' => '#4a4a4a'],
            ['id' => 'overall_position', 'type' => 'static_text', 'text' => 'GENERAL {{overall_position}}', 'visible_when' => 'overall_position', 'x_mm' => 3, 'y_mm' => 32.5, 'width_mm' => 24, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 3.2, 'font_weight' => 400, 'text_align' => 'left', 'color' => '#4a4a4a'],
            ['id' => 'category', 'type' => 'static_text', 'text' => 'CAT {{category}}', 'visible_when' => 'category', 'x_mm' => 27, 'y_mm' => 32.5, 'width_mm' => 27, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 3.2, 'font_weight' => 400, 'text_align' => 'left', 'color' => '#4a4a4a'],
            ['id' => 'bib', 'type' => 'dynamic_text', 'field' => 'bib_number', 'visible_when' => 'bib_number', 'text' => '#{{bib_number}}', 'x_mm' => 45, 'y_mm' => 3, 'width_mm' => 12, 'height_mm' => 3, 'font_family' => 'Inter', 'font_size_pt' => 3.5, 'font_weight' => 400, 'text_align' => 'right', 'color' => '#4a4a4a'],
            ['id' => 'serial', 'type' => 'serial', 'text' => '{{plate_serial}}', 'x_mm' => 3, 'y_mm' => 34.5, 'width_mm' => 40, 'height_mm' => 2.2, 'font_family' => 'Inter', 'font_size_pt' => 2.6, 'font_weight' => 400, 'text_align' => 'left', 'color' => '#8a8a8a'],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function cyclingBack(): array
    {
        return [
            ...$this->backSkeleton(),
            ['id' => 'icon_cycling', 'type' => 'vector_icon', 'icon' => 'cycling', 'x_mm' => 4, 'y_mm' => 13.3, 'width_mm' => 12, 'height_mm' => 11, 'stroke' => '#8a7530', 'stroke_width_mm' => 0.4],
            ['id' => 'icon_trophy', 'type' => 'vector_icon', 'icon' => 'trophy', 'x_mm' => 45, 'y_mm' => 13.3, 'width_mm' => 11, 'height_mm' => 11, 'stroke' => '#8a7530', 'stroke_width_mm' => 0.4],
        ];
    }
}
