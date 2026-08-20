# Events media — asset contract

Real event cover photos come from the backend (`edition.event.cover_url`) —
`EventCard.vue` already uses them when present. This folder only holds the
**brand fallback** for events that don't have a cover photo yet.

| Archivo | Estado | Qué debe mostrar |
|---|---|---|
| `event-placeholder.webp` | FALTA ASSET | Textura/composición de marca (asfalto, línea de meta, Legacy Line) — no un rectángulo gris genérico. 16:9. |

Sin este archivo, `EventCard.vue` mantiene su fallback actual (nombre del
deporte en tipografía grande sobre textura diagonal), que ya es
intencional, no un placeholder gris.
