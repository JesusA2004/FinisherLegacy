# Hero media — asset contract

Rendered by `resources/js/components/public/media/HomeHeroMedia.vue`.

| Archivo | Estado | Qué muestra |
|---|---|---|
| `finisher-hero-desktop.mp4` | **LISTO** | Video de fondo full-bleed del Hero (desktop, `lg`+). Autoplay, muted, loop, sin controles. |
| `finisher-hero-desktop.webm` | FALTA (opcional) | Transcode WebM del mismo video — mejoraría peso/compatibilidad, no bloquea nada mientras el `.mp4` exista. |
| `finisher-hero-poster.webp` | FALTA (opcional) | Frame fijo de esa escena — solo se usaría si algún día el video deja de reproducirse en algún navegador. Hoy el fallback es directamente la escena CSS. |
| `finisher-hero-poster-mobile.webp` | FALTA (opcional) | Recorte vertical para mobile — el componente no reproduce video por debajo de `sm`, por costo de datos; mobile usa la escena CSS. |

Con solo el `.mp4` presente, `HomeHeroMedia.vue` ya reproduce el video
directamente en desktop (sin `prefers-reduced-motion`) y cae a la escena
CSS (líneas de pista + luz dorada) en mobile, reduced-motion, o si el
video falla al cargar.
