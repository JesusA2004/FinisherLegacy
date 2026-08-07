# Brand assets — pendientes

Esta carpeta es donde deben colocarse los assets de marca reales cuando estén
disponibles. Ninguno de los siguientes existe todavía en el repositorio; el
sitio usa componentes Vue (SVG/CSS) como sustituto mientras tanto, nunca
fotografías genéricas de stock.

| Asset | Archivo esperado | Usado por | Estado |
|---|---|---|---|
| Logotipo (símbolo FL) | `logo-mark.svg` | `FinisherLegacyLogo.vue` | Sustituido por monograma SVG propio |
| Logotipo (wordmark completo) | `logo-wordmark.svg` | `FinisherLegacyLogo.vue` | Sustituido por texto tipográfico |
| Mascota — pose completa | `mascot-hero.png` / `.webp` | `MascotSpotlight.vue` | Placeholder ilustrado, pendiente de arte final |
| Mascota — variantes de expresión | `mascot-*.png` | `MascotEmptyState.vue` y estados vacíos futuros | Pendiente |
| Placa Finisher Legacy (fotografía de producto) | `plate-product.jpg` | Home / How it works | Sustituido por `PlateShowcase.vue` (mockup CSS) |
| Medallas (fotografía real) | `medals/*.jpg` | Colección de medallas del Athlete | El Athlete sube sus propias fotos vía `medal_images` |
| Fotografía de eventos (cover/hero) | `events/*.jpg` | Cards y detalle de evento | Cada evento sube su propio `cover_path` desde `/admin` (Bloque 5) |

## Cómo reemplazar un placeholder

1. Coloca el archivo final en esta carpeta (o en el disco `public` de Storage
   si es contenido subido por un usuario, no un asset de marca estático).
2. Actualiza el componente correspondiente para apuntar a
   `/images/brand/<archivo>` en lugar del mockup SVG/CSS.
3. No borres el mockup SVG sin confirmar que el archivo final ya carga
   correctamente — sirve como *fallback* si la imagen no existe.
