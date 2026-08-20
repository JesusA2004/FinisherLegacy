<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storage — piloto
    |--------------------------------------------------------------------------
    |
    | Finisher Legacy está en fase piloto: el almacenamiento NO es ilimitado.
    | Todos los límites de archivos/cuotas viven aquí — nunca hardcodeados en
    | controllers o Form Requests — para poder subirlos sin migración cuando
    | el piloto crezca.
    */

    'image' => [
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'max_original_kb' => 8192,
        'thumbnail_size' => 400,
        'display_max_width' => 1800,

        // El piloto no necesita conservar el original gigante una vez que
        // existe una versión optimizada de alta calidad. Cambiar a false
        // para dejar de guardarlos y ahorrar almacenamiento; ya guardados
        // no se borran retroactivamente por este cambio.
        'keep_original' => true,
    ],

    'profile' => [
        'avatar' => [
            'max_kb' => 3072,
        ],
        'cover' => [
            'max_kb' => 5120,
        ],
    ],

    'medal' => [
        'front' => [
            'max_kb' => 8192,
        ],
        'back' => [
            'max_kb' => 8192,
        ],
        'gallery' => [
            'max_kb' => 8192,
            'max_files' => 3,
        ],
        // front + back + gallery, del lado del servidor (independiente de
        // cuántos campos exponga el formulario).
        'max_images_per_medal' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Video — piloto
    |--------------------------------------------------------------------------
    |
    | DESHABILITADO A PROPÓSITO. La infraestructura actual no tiene
    | procesamiento seguro de video (no hay ffmpeg/cola de transcoding
    | instalada), y el proyecto no debe arriesgarse a subir video sin
    | validarlo/transcodificarlo correctamente. El esquema queda preparado
    | para cuando se aborde con la herramienta adecuada; no se habilita el
    | upload mientras `enabled` sea false.
    */
    'video' => [
        'enabled' => false,
        'max_seconds' => 30,
        'max_mb' => 25,
        'mimes' => ['mp4', 'mov'],
        'max_per_medal' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cuotas globales por atleta — piloto
    |--------------------------------------------------------------------------
    */
    'quotas' => [
        'max_medals_per_athlete' => 30,
        'max_images_per_athlete' => 150,
        'max_videos_per_athlete' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Device / Production API (ADR 0002, Slice 1)
    |--------------------------------------------------------------------------
    |
    | Tunables for pairing, heartbeat and job-claim leases — centralized here
    | instead of hardcoded in the Actions/Services that use them, so a real
    | deployment can tune timings without touching code.
    */

    // A device counts as "online" if its last heartbeat is within this many
    // seconds. Deliberately NOT a persisted "offline" status — a device
    // that stops sending heartbeats doesn't need a background job to flip
    // a flag; ProductionDevice::isOnline() derives it from `last_seen_at`
    // on read. See App\Enums\ProductionDeviceStatus.
    'device_online_timeout_seconds' => env('FINISHER_DEVICE_ONLINE_TIMEOUT_SECONDS', 90),

    // How long a claimed ProductionJob's lease lasts before it becomes
    // reclaimable by another device — ONLY while the job is Assigned or
    // Preparing (ProductionJob::isSafeToRelease()). Once physical
    // engraving starts (engraving_front onward) the lease stops mattering
    // entirely: there is real, irreversible physical state on the plate,
    // so a job is never auto-reclaimed out from under a device past that
    // point — see docs/adr/0003-production-state-machine.md §Lease.
    'device_lease_seconds' => env('FINISHER_DEVICE_LEASE_SECONDS', 900),

    // How long a pairing code/poll token stays valid before a desktop must
    // request a new one. Short on purpose — pairing is a one-time,
    // attended (Super Admin present) handshake, not a long-lived credential.
    'pairing_expiration_minutes' => env('FINISHER_PAIRING_EXPIRATION_MINUTES', 10),

    // Length of the human-readable pairing code shown on the desktop
    // screen and in the admin "Estaciones" list. Not a secret — the real
    // secret is the poll token the desktop keeps privately (docs/adr/0002).
    'pairing_code_length' => env('FINISHER_PAIRING_CODE_LENGTH', 6),

    // How long a stored device idempotency response is honored before a
    // repeated Idempotency-Key is treated as a new request.
    'idempotency_ttl_seconds' => env('FINISHER_IDEMPOTENCY_TTL_SECONDS', 86400),

    // Private disk (never a public URL) for frozen ProductionArtifact
    // files — served only through the authenticated, ownership-checked
    // Device API artifact endpoint. See
    // App\Services\Production\ProductionArtifactService.
    'production_artifact_disk' => env('FINISHER_PRODUCTION_ARTIFACT_DISK', 'local'),
];
