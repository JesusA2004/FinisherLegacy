<?php

namespace App\Enums;

/**
 * Which official preset a template follows — drives which starting
 * front/back layout the admin gets when building a new molde. Purely
 * descriptive metadata; does not change rendering behavior by itself.
 */
enum PlateSportType: string
{
    case Running = 'running';
    case Triathlon = 'triathlon';
    case Cycling = 'cycling';
    case Trail = 'trail';
    case Generic = 'generic';

    public function label(): string
    {
        return match ($this) {
            self::Running => 'Running',
            self::Triathlon => 'Triatlón',
            self::Cycling => 'Ciclismo',
            self::Trail => 'Trail',
            self::Generic => 'Genérico',
        };
    }
}
