<?php

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'email_verified' => $this->email_verified_at !== null,
            'legacy_id' => $this->whenLoaded('legacyId', fn () => $this->legacyId?->code),
            // Minimal — the canonical Athlete identity (docs/adr/0004),
            // not a CRM view of it: a client needs to know "this account
            // is linked to an Athlete," not their full history.
            'athlete' => $this->whenLoaded('athlete', fn () => $this->athlete === null ? null : [
                'id' => $this->athlete->id,
                'full_name' => $this->athlete->full_name,
            ]),
            // Permission names straight from Spatie/App\Support\PermissionCatalog
            // — never inferred from a role name, so a client never has to
            // hardcode "if role === admin" (docs/api/v1.md §Capabilities).
            // Not gated behind whenLoaded(): Spatie's getAllPermissions()
            // resolves roles/permissions itself if not already eager
            // loaded — a single extra query on a one-user `/me` call is a
            // fine trade for never silently omitting this field.
            'permissions' => $this->getAllPermissions()->pluck('name')->values(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
