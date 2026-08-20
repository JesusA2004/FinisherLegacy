# Hero media — asset contract

Rendered by `resources/js/components/public/media/HomeHeroMedia.vue`, which
already implements the video → poster → CSS-scene cascade below. Drop files
in with these **exact names** and they take over automatically — no code
changes needed.

| Archivo | Estado | Qué debe mostrar |
|---|---|---|
| `finisher-hero-desktop.webm` | FALTA ASSET | Corredor cruzando/inmediatamente después de la meta. Iluminación dramática (amanecer/tarde), espacio negativo a la izquierda para el H1. Loop de 5–10s. |
| `finisher-hero-desktop.mp4` | FALTA ASSET | Mismo contenido que el `.webm`, como fallback de códec. |
| `finisher-hero-poster.webp` | FALTA ASSET | Frame fijo de esa misma escena — se usa como `poster` del video y como imagen si el video falla. ~2400×1350 (16:9). |
| `finisher-hero-poster-mobile.webp` | FALTA ASSET | Recorte vertical de la misma escena, ~1080×1440 (3:4). Se usa en vez del video en mobile (el componente no reproduce video por debajo de 640px, por costo de datos). |

Sin estos archivos, `HomeHeroMedia.vue` cae en la escena CSS actual (líneas
de pista + luz dorada de amanecer) — el layout y el espacio del hero no
cambian cuando lleguen los archivos reales, solo la capa de fondo.
