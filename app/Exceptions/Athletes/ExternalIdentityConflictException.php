<?php

namespace App\Exceptions\Athletes;

use RuntimeException;

/**
 * A merge would move an external identity onto an Athlete that already
 * has a DIFFERENT one with the same provider/subject — a real collision,
 * never silently skipped. See docs/adr/0004 §External identity conflict.
 */
class ExternalIdentityConflictException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('No se puede fusionar: una identidad externa ya pertenece a otro atleta.');
    }

    public function code(): string
    {
        return 'EXTERNAL_IDENTITY_CONFLICT';
    }
}
