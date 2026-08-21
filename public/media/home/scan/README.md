# Scan media — asset contract

Rendered by `resources/js/components/public/media/LegacyScanMedia.vue`,
which wraps the CSS phone-scanning scene in `LegacyCodePreview.vue`
(unchanged — scan line, micro-grid, scanning/found states) and adds a
small process chain underneath: TELÉFONO → LEGACY CODE → SCAN → LEGACY
ENCONTRADO.

| Archivo | Estado | Qué muestra |
|---|---|---|
| `scan-phone.png` | **LISTO** | Usado como el nodo "Teléfono" del chain (thumbnail circular). |
| `scan-result.webp` | FALTA | Pantalla del teléfono mostrando el Legacy Profile ya abierto — cuando exista, se puede volver a habilitar el modo foto-a-foto (scan → result) que había antes. |
| `legacy-scan.webm` / `.mp4` | FALTA (opcional) | Video corto del gesto de escaneo completo. |

`scan-phone.png` está referenciado directamente (no se prueba con
`useAssetExists` — ya sabemos que existe).
