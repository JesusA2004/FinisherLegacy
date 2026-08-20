# Plate media — asset contract

Rendered by `resources/js/components/public/media/PlateMedia.vue`. If the
front photo exists, it replaces the CSS-drawn plate (`PlateShowcase.vue`)
with a real photo the visitor can flip front/back; without it, the CSS
plate — which already has tilt + glare + the sample data — keeps working
exactly as it does today.

| Archivo | Estado | Qué debe mostrar |
|---|---|---|
| `legacy-plate-front.webp` | FALTA ASSET | Placa real, anverso — nombre, evento, tiempo, Legacy Code visibles. |
| `legacy-plate-back.webp` | FALTA ASSET | Placa real, reverso — grabado/detalle. |
| `legacy-plate-detail.webp` | FALTA ASSET | Macro del grabado o del metal. |
| `legacy-plate-motion.webm` / `.mp4` | opcional | Video macro corto (placa girando o luz recorriendo el metal). |

Fondo transparente cuando sea posible, mínimo ~1600px de lado largo.
