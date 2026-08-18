<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A workflow label an operator recognizes ("Fiber 30W — LightBurn"), not a
 * driver — Finisher Legacy never talks to the laser directly. See
 * docs/plate-production.md. Deliberately has no power/speed/frequency: those
 * depend on material/finish/lens and must be calibrated physically.
 */
#[Fillable(['name', 'type', 'software', 'default_format', 'width_mm', 'height_mm', 'active'])]
class MachineProfile extends Model
{
    protected function casts(): array
    {
        return [
            'width_mm' => 'decimal:2',
            'height_mm' => 'decimal:2',
            'active' => 'boolean',
        ];
    }
}
