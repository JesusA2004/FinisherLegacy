<?php

namespace App\Enums;

/**
 * Reasons a physical engraving attempt failed — deliberately generic
 * (never a real driver/SDK error name, since no real driver exists yet —
 * see docs/adr/0002 and docs/adr/0003 §No SDK real).
 */
enum ProductionFailureCode: string
{
    case DeviceNotConnected = 'device_not_connected';
    case LaserNotReady = 'laser_not_ready';
    case EngravingFailed = 'engraving_failed';
    case QrUnreadable = 'qr_unreadable';
    case UserCancelled = 'user_cancelled';
    case Unknown = 'unknown';
}
