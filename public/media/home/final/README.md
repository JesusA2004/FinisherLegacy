# Final CTA media — asset contract

Rendered by `resources/js/components/public/media/FinalMedia.vue`, used as
the full-bleed background of the closing `CTASection` on Home
(`cinematic` prop).

| Archivo | Estado | Qué muestra |
|---|---|---|
| `legacy-final-poster.jpeg` | **LISTO** | Atleta celebrando meta en mano, blanco y negro de alto contraste. Fondo estructural de la escena de cierre. |
| `legacy-final.webm` / `.mp4` | FALTA (opcional) | Si aparece, reemplazaría el JPEG como fondo en loop — hoy `FinalMedia.vue` solo referencia el JPEG (no hay `<source>` apuntando a un video inexistente). |

`legacy-final-poster.jpeg` está referenciado directamente (no se prueba
con `useAssetExists` — ya sabemos que existe).
