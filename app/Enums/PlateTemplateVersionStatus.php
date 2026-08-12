<?php

namespace App\Enums;

enum PlateTemplateVersionStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
