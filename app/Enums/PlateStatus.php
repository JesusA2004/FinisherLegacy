<?php

namespace App\Enums;

enum PlateStatus: string
{
    case Draft = 'draft';
    case PendingConfirmation = 'pending_confirmation';
    case Queued = 'queued';
    case Processing = 'processing';
    case Produced = 'produced';
    case QualityCheck = 'quality_check';
    case Ready = 'ready';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Reprint = 'reprint';
}
