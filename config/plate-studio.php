<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Fuentes para PNG/PDF
    |--------------------------------------------------------------------------
    |
    | El SVG de producción usa font-family CSS con fallback (el software que
    | abre el archivo resuelve la fuente). PNG (GD) y PDF (dompdf) necesitan un
    | archivo TTF real embebido. Por defecto se usa una fuente del sistema
    | (documentado como dependencia, punto 31 del pedido) — sustituir por
    | Inter.ttf/Inter-Bold.ttf en storage/fonts cuando estén disponibles y
    | actualizar estas rutas.
    |
    */
    'fonts' => [
        'regular' => env('PLATE_STUDIO_FONT_REGULAR', 'C:\\Windows\\Fonts\\arial.ttf'),
        'bold' => env('PLATE_STUDIO_FONT_BOLD', 'C:\\Windows\\Fonts\\arialbd.ttf'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Lotes de exportación
    |--------------------------------------------------------------------------
    */
    'batch_export_limit' => 50,
];
