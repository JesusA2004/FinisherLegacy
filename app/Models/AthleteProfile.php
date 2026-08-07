<?php

namespace App\Models;

use App\Enums\ProfileVisibility;
use Database\Factories\AthleteProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'username', 'bio', 'birth_date', 'city', 'state', 'country',
    'main_sport_id', 'profile_visibility', 'profile_photo_path', 'cover_photo_path',
])]
class AthleteProfile extends Model
{
    /** @use HasFactory<AthleteProfileFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'profile_visibility' => ProfileVisibility::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Sport, $this> */
    public function mainSport(): BelongsTo
    {
        return $this->belongsTo(Sport::class, 'main_sport_id');
    }
}
