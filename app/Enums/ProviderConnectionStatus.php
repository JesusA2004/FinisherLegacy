<?php

namespace App\Enums;

enum ProviderConnectionStatus: string
{
    case Untested = 'untested';
    case Connected = 'connected';
    case Failed = 'failed';
}
