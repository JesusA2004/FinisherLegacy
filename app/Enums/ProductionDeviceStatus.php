<?php

namespace App\Enums;

/**
 * Deliberately has no `Offline` case. Whether a device is reachable right
 * now is a function of `last_seen_at` (see ProductionDevice::isOnline()),
 * not a value an admin or a cron job has to keep in sync — persisting
 * "offline" would mean either a scheduled job flipping it every N seconds
 * (a moving part that can itself fall behind) or a stale flag nobody
 * updates. `Active` covers "paired and allowed to work"; whether it's
 * currently online is derived on read, every time, from real heartbeat data.
 */
enum ProductionDeviceStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Revoked = 'revoked';
}
