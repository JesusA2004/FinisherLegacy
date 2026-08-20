<?php

namespace App\Contracts;

/**
 * Implemented by both `App\Models\User` and `App\Models\ProductionDevice` —
 * lets every production Action (app/Actions/Production/*) accept "whoever
 * is doing this" without branching on `instanceof` everywhere, and without
 * ever putting a device id in a `*_by` column typed for a user (see
 * docs/adr/0003-production-state-machine.md §Actor).
 */
interface ProductionActor
{
    /**
     * Spanish, human-readable, for ActivityLog descriptions — e.g.
     * "Jesús" or "la estación EVENT-01". Admin Auditoría composes full
     * sentences around this (App\Http\Controllers\Admin\AuditController).
     */
    public function productionActorLabel(): string;
}
