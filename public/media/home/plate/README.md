# Plate media — asset contract

Rendered by `resources/js/components/public/media/PlateMedia.vue`. If the
front photo exists, it replaces the CSS-drawn plate (`PlateShowcase.vue`)
with a real photo the visitor can flip front/back; without it, the CSS
plate keeps working exactly as it does today.

**None of these exist yet — everything below is FALTA.** In the meantime
`PlateShowcase.vue` was rebuilt to represent the actual product: a short
engraved metal bar (`aspect-[3/1]`, not a card), two lateral attachment
loops, and ribbon glimpses above/below — used as-is in the Legacy Code
section, the Hero (`HeroPlateFeature.vue`), the sticky medal→plate morph
(`StickyLegacyJourney.vue`), and the mount micro-demo
(`PlateMountDemo.vue`), so the geometry never drifts between spots even
before real photography exists.

| Archivo | Estado | Qué debe mostrar |
|---|---|---|
| `legacy-plate-front.webp` | FALTA | Placa real, anverso — nombre, evento, tiempo, Legacy Code visibles. |
| `legacy-plate-back.webp` | FALTA | Placa real, reverso — grabado/detalle. |
| `legacy-plate-detail.webp` | FALTA | Macro del grabado o del metal. |
| `legacy-plate-motion.webm` / `.mp4` | FALTA (opcional) | Video macro corto (placa girando o luz recorriendo el metal). |

Fondo transparente cuando sea posible, mínimo ~1600px de lado largo.
