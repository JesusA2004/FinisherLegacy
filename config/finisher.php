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

];
