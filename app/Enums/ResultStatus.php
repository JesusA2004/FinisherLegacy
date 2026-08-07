<?php

namespace App\Enums;

enum ResultStatus: string
{
    case Pending = 'pending';
    case Unofficial = 'unofficial';
    case Finished = 'finished';
    case Dnf = 'dnf';
    case Dns = 'dns';
    case Dq = 'dq';
    case Verified = 'verified';
}
