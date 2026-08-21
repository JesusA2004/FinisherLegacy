# Brand assets

Los assets reales de marca ya están en el repositorio. Este documento
describe qué archivo se usa dónde y por qué, para que nadie vuelva a
hardcodear una ruta de imagen en una página.

**Regla:** ningún componente de página debe apuntar directamente a
`/images/brand/...`. Todo pasa por `FinisherLegacyLogo.vue` (logo) o
`FinisherMascot.vue` (mascota).

## Logo — originales del usuario (no tocar/borrar)

Carpeta `logo/`. Todos son el mismo lockup "FL" + "TU HISTORIA. TU LEGADO."
en distintas combinaciones de color; no existe un wordmark horizontal
separado ni una versión solo-símbolo entregada por el usuario.

| Archivo | Color FL / tagline | Fondo |
|---|---|---|
| `FINISHER LEGACY-02.png` | dorado / negro | transparente |
| `findoradotransparente.png` | dorado / negro | transparente |
| `findoradodoradotransparente.png` | dorado / dorado | transparente |
| `finblancotransparente.png` | blanco / blanco | transparente |
| `finblancodoradotransparente.png` | blanco / dorado | transparente |
| `finnegrotransparente.png` | negro / negro | transparente |
| `finnegrotransparentedorado.png` | negro / dorado | transparente |
| `finblanco.jpeg` | blanco / blanco | gris claro opaco |
| `finblancoDorado.jpeg` | blanco / dorado | gris claro opaco |
| `findoradofondoblanco.jpeg` | dorado / dorado | gris claro opaco |
| `findoradofondoblancobegro.jpeg` | dorado / negro | gris claro opaco |
| `findoradofondoblancoo.jpeg` | dorado / blanco | gris claro opaco |
| `finnegrofondoblanco.jpeg` | negro / negro | gris claro opaco |
| `finnegrofondoblancodorado.jpeg` | negro / dorado | gris claro opaco |

Los `.jpeg` con fondo gris opaco son redundantes con los `.png`
transparentes (misma combinación de color, solo que "horneada" sobre un
fondo plano) — se conservan pero no se usan en producción porque el `.png`
transparente sirve sobre cualquier fondo.

## Logo — derivados internos (generados desde los originales de arriba)

Estos son los que consume `FinisherLegacyLogo.vue`. Se generaron con GD:
`logo-mark-*` recortando solo el glifo "FL" (hay un hueco vacío limpio
entre el glifo y el tagline en el PNG original, así que el recorte es
exacto, sin tocar el arte); `logo-horizontal-*` son el lockup completo
reescalado a un ancho máximo razonable.

| Archivo | Contenido | Uso previsto |
|---|---|---|
| `logo-mark-gold.png` | solo "FL", dorado | favicon, sidebar colapsado |
| `logo-mark-white.png` | solo "FL", blanco | mark sobre fondo oscuro |
| `logo-mark-black.png` | solo "FL", negro | mark sobre fondo claro |
| `logo-mark.png` | copia de `logo-mark-gold.png` | valor por defecto |
| `logo-horizontal-light.png` | "FL" blanco + tagline dorado | navbar, footer, sidebar expandido, auth (todo fondo oscuro) |
| `logo-horizontal-gold.png` | "FL" + tagline, todo dorado | hero premium / OG sobre fondo oscuro |
| `logo-horizontal-dark.png` | "FL" negro + tagline dorado | uso futuro sobre fondo claro |

Si se agrega un nuevo pose/variante de marca, regenerar corriendo el mismo
pipeline (no está versionado como script del repo; ver historial de esta
sesión) — nunca editar estos PNG a mano.

## Mascota

Carpeta `mascot/`. `mascot-hero.jpeg` es la única pose entregada: render 3D,
JPEG opaco (sin alpha) con fondo gris muy claro, retrato 916×1600.

`FinisherMascot.vue` la consume para las 4 variantes (`hero`, `small`,
`empty`, `success`) — hoy las 4 apuntan a la misma imagen; el prop `variant`
ya existe para que, cuando lleguen más poses, solo haya que tocar el mapa
`poses` dentro del componente, no cada página que la usa.

Como el JPEG no tiene transparencia, el componente no intenta quitarle el
fondo con blend-mode (eso destruiría los tonos dorados del propio
personaje) — en su lugar aplica una máscara radial suave que desvanece el
fondo claro hacia los bordes, más una sombra dorada. Si en el futuro se
entrega una versión PNG/WebP con alpha real, cambiar el mapa `poses` y
quitar la máscara en `FinisherMascot.vue`.

## Favicon / app icons / Open Graph

Generados a partir de `logo-mark-gold.png` (el mark real, no una
aproximación):

- `favicon.ico`, `favicon-16x16.png`, `favicon-32x32.png` — mark dorado,
  fondo transparente.
- `apple-touch-icon.png`, `android-chrome-192x192.png`,
  `android-chrome-512x512.png` — mark dorado sobre `#09090B` (fl-black)
  sólido, con margen de seguridad para máscaras Android.
- `favicon.svg` — **no se tocó**: es un monograma vectorial dibujado a mano
  que ya se parecía razonablemente al FL real (mismo corte diagonal),
  y no existe una fuente vectorial del logo real para reemplazarlo sin
  pérdida de calidad. Si alguna vez se obtiene el `.ai`/`.svg` original del
  logo, reemplazar este archivo y regenerar los PNG de arriba desde ahí en
  vez de este README.
- `public/images/brand/og-finisher-legacy.png` (1200×630, LISTO) — mismo
  lockup (mark dorado + "FINISHER LEGACY" + "TU HISTORIA. TU LEGADO.") que
  el `.jpg` original, pero recompuesto: el `.jpg` tenía el lockup pegado
  abajo-a-la-izquierda con casi la mitad derecha vacía ("se ve fea, no está
  centrada" — feedback 2026-08-21). Recortado y re-centrado sobre un fondo
  degradado limpio vía un script PHP+GD puntual (no hay ImageMagick/sharp/
  Playwright disponibles en este entorno para generar uno nuevo desde cero;
  el script vive fuera del repo, en el scratchpad de esa sesión). El
  `.jpg` original **sigue existiendo, sin usar** — se dejó por si se
  prefiere borrarlo manualmente. Referenciado desde `og:image` /
  `twitter:image` en `app.blade.php` (y ya no en ningún otro sitio).

## Cómo reemplazar / agregar un asset

1. Coloca el archivo final en `logo/` o `mascot/` (nunca borres los
   originales del usuario sin confirmar antes).
2. Si es logo: añade la ruta al mapa `sources` en
   `resources/js/components/public/FinisherLegacyLogo.vue`.
3. Si es mascota: añade la ruta al mapa `poses` en
   `resources/js/components/public/FinisherMascot.vue`.
4. No hardcodees la ruta en ninguna página — siempre a través del
   componente.
