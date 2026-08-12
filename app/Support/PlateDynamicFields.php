<?php

namespace App\Support;

/**
 * Catalog of `{{field}}` tokens a template element can bind to. This is the single
 * list the editor's field picker, PlateRenderData's resolver, and the docs all read
 * from — adding a field means adding one entry here, not scattering strings.
 */
class PlateDynamicFields
{
    /**
     * @return array<string, array{label: string, group: string}>
     */
    public static function catalog(): array
    {
        return [
            'athlete_name' => ['label' => 'Nombre del atleta', 'group' => 'Atleta'],
            'bib_number' => ['label' => 'Número de dorsal', 'group' => 'Atleta'],
            'event_name' => ['label' => 'Nombre del evento', 'group' => 'Evento'],
            'event_date' => ['label' => 'Fecha del evento', 'group' => 'Evento'],
            'race_name' => ['label' => 'Carrera / distancia', 'group' => 'Evento'],
            'distance' => ['label' => 'Distancia', 'group' => 'Evento'],
            'official_time' => ['label' => 'Tiempo oficial', 'group' => 'Resultado'],
            'pace' => ['label' => 'Ritmo', 'group' => 'Resultado'],
            'overall_position' => ['label' => 'Posición general', 'group' => 'Resultado'],
            'category' => ['label' => 'Categoría', 'group' => 'Resultado'],
            'category_position' => ['label' => 'Posición en categoría', 'group' => 'Resultado'],
            'swim_time' => ['label' => 'Tiempo de natación', 'group' => 'Splits'],
            'bike_time' => ['label' => 'Tiempo de ciclismo', 'group' => 'Splits'],
            'run_time' => ['label' => 'Tiempo de carrera', 'group' => 'Splits'],
            'personal_phrase' => ['label' => 'Frase personal', 'group' => 'Placa'],
            'legacy_code' => ['label' => 'Legacy Code', 'group' => 'Placa'],
            'plate_serial' => ['label' => 'Serial de la placa', 'group' => 'Placa'],
        ];
    }

    public static function label(string $field): string
    {
        return self::catalog()[$field]['label'] ?? $field;
    }

    public static function isKnown(string $field): bool
    {
        return isset(self::catalog()[$field]);
    }
}
