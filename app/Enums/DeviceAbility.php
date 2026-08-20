<?php

namespace App\Enums;

/**
 * Sanctum token abilities issued to a ProductionDevice. A device token can
 * never be granted anything outside this list — no `users`/`roles`/
 * `settings`/`templates`/admin ability exists here by construction, so a
 * stolen device token is scoped to production floor actions only.
 *
 * `ProductionUpdate` and `ProductionEngrave` are reserved for Slice 2/3
 * (checklist + engrave-progress reporting) — issued on every device token
 * now so pairing doesn't need to happen again when those endpoints ship,
 * but nothing in Slice 1 checks for them yet.
 */
enum DeviceAbility: string
{
    case Heartbeat = 'device:heartbeat';
    case ProductionRead = 'production:read';
    case ProductionClaim = 'production:claim';
    case ProductionUpdate = 'production:update';
    case ProductionEngrave = 'production:engrave';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_map(fn (self $ability) => $ability->value, self::cases());
    }
}
