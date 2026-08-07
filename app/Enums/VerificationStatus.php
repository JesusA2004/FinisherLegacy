<?php

namespace App\Enums;

enum VerificationStatus: string
{
    case Unverified = 'unverified';
    case Verified = 'verified';
    case Disputed = 'disputed';
}
