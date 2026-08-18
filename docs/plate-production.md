# Producción de placas: de molde a placa entregada

Este documento describe el flujo completo de Plate Studio: cómo se diseña un
molde de **dos caras**, cómo se asigna a un evento, cómo se genera una placa
real, y cómo se exportan los archivos que van a la máquina de grabado láser.
Ver también `docs/legacy-code-lifecycle.md` para la regla de permanencia del
Legacy Code.

**Regla central: Finisher Legacy produce DOS archivos de grabado por
placa, uno por cara.** Nunca uno solo con ambas caras mezcladas (salvo un
layout de producción especial que hoy no existe).

```
Finisher Legacy → SVG (frente + reverso, por separado)
                → LightBurn
                → Fiber Laser
                → grabar frente
                → voltear la placa (jig)
                → grabar reverso
                → escanear QR
                → Lista
```

## 1. Conceptos

| Concepto | Qué es | Dónde vive |
|---|---|---|
| **Plate Template** (Molde) | Metadatos de un diseño de placa: nombre, dimensiones físicas (mm), material, orientación, margen de seguridad, tipo de deporte (`sport_type`), transformación del reverso (`back_transform`), tamaño mínimo de QR validado físicamente (`minimum_validated_qr_size_mm`). | `plate_templates` / `App\Models\PlateTemplate` |
| **Plate Template Version** | Una revisión concreta del diseño: configuración de **frente y reverso juntas** (lista de elementos cada una), estado (`draft`/`published`/`archived`). | `plate_template_versions` / `App\Models\PlateTemplateVersion` |
| **Event Plate Template** | Qué versión de molde usa una edición de evento (o una carrera específica dentro de ella) por defecto. | `event_plate_templates` / `App\Models\EventPlateTemplate` |
| **Plate** | Una placa concreta para una persona: snapshot de sus datos en el momento de generarla, más la versión de molde usada. | `plates` / `App\Models\Plate` |
| **Legacy Code** | Identidad permanente de la placa. Ver `docs/legacy-code-lifecycle.md`. | `legacy_codes` / `App\Models\LegacyCode` |
| **Production Job** | Un trabajo en el kanban de `/production`. Una placa puede tener varios a lo largo de su vida (reimpresiones). | `production_jobs` / `App\Models\ProductionJob` |
| **Machine Profile** | Una etiqueta de flujo de trabajo ("Fiber 30W — LightBurn"), **no un driver** — Finisher Legacy nunca controla el láser directamente. | `machine_profiles` / `App\Models\MachineProfile` |

El **renderer único** (`App\Services\PlateTemplateRenderService`) es lo que
convierte una versión de molde + los datos de una placa en SVG, para
cualquiera de las dos caras (`front`/`back`). El export a PNG
(`GdPlateRenderer`) y PDF (`PdfExportService`) parten de la misma lista de
elementos resuelta por ese servicio — nunca se recalculan posiciones ni
auto-fit por separado para cada formato.

## 2. La regla de las dos caras

**CARA A (frente): datos deportivos.** Prioriza legibilidad — evento, fecha,
atleta, tiempo, splits/distancia/ritmo según el deporte, categoría/posición,
serial. **Sin QR por defecto.**

**CARA B (reverso): Legacy.** QR grande y sin competencia por espacio +
Legacy Code + "Finisher Legacy" +, opcionalmente, frase personal e
iconografía temática simple (líneas/texto — ver §3). Es la cara que el
atleta escanea para reclamar su Legacy.

Los tres presets oficiales (`database/seeders/PlateTemplateSeeder.php`) ya
siguen esta regla:

| Preset | `sport_type` | Frente | Reverso |
|---|---|---|---|
| Triathlon Premium | `triathlon` | splits SWIM/BIKE/RUN, age group, general | QR + Legacy Code + "SWIM · BIKE · RUN" |
| Running Classic | `running` | distancia, ritmo, general, categoría | QR + Legacy Code + motivo de pista |
| Cycling Classic | `cycling` | distancia, velocidad/ritmo, general, categoría | QR + Legacy Code + líneas de velocidad |

El editor **no prohíbe** poner un QR en el frente — un admin puede hacerlo
para un molde custom — pero ningún preset oficial lo hace.

### Elementos condicionales (`visible_when`)

Un elemento con `'visible_when' => 'swim_time'` desaparece por completo
(etiqueta incluida) si ese campo viene vacío, en vez de mostrar `SWIM ` sin
valor. Úsalo en cualquier campo opcional (splits, ritmo, categoría, dorsal,
frase). Implementado en `PlateTemplateRenderService::resolveElements()`.

### Iconografía temática

Es parte del **molde**, no se genera por placa. Hoy el renderer solo dibuja
primitivos vectoriales: `static_text`/`dynamic_text`/`serial`, `line`,
`rect`, `qr`, `image`/`logo`/`icon` (imagen embebida). No existe todavía un
tipo `path`/`icon` para dibujar pictogramas (nadador, bicicleta, montaña) —
los presets actuales simulan el motivo con texto espaciado y líneas
(`"SWIM · BIKE · RUN"`, líneas de pista/velocidad). Añadir un primitivo
vectorial de icono real es trabajo futuro documentado, no algo que un admin
pueda "activar" hoy.

### Marcas protegidas

Los presets genéricos (Running/Triathlon/Cycling/Trail) **solo usan** el
wordmark de Finisher Legacy e iconografía propia — nunca el logo de un
evento/organizador real. Un logo de evento solo aparece si el admin lo sube
explícitamente a ese Template/Event vía un elemento `image`/`logo`.

## 3. Crear un molde

1. Entrar a `/admin/plate-studio` (permiso `platetemplates.manage`).
2. "Nuevo molde": nombre, ancho/alto en mm, orientación, margen de
   seguridad. Se crea el `PlateTemplate` y su primera versión en estado
   `draft`.
3. En el editor: pestañas **[FRENTE] [REVERSO]** prominentes en la barra
   superior (no escondidas), cada una con su propio lienzo. Cambiar de
   pestaña **no pierde** los cambios de la otra cara — ambas viven en
   memoria (`elementsFront`/`elementsBack`) hasta guardar.
4. El toggle **Vista producto / Vista grabado** cambia entre los colores
   configurados y blanco/negro puro (lo que realmente se graba — sin metal,
   sombras ni glow).
5. "Guardar" (Ctrl/Cmd+S) guarda **ambas caras juntas** en un solo request.
   "Publicar" congela la versión completa (frente V1 + reverso V1 son
   siempre la misma versión — nunca se versionan por separado, así se evita
   que una placa histórica termine con "front V1 + back V3"). Para cambiar
   el diseño hay que crear una nueva versión ("Nueva versión" duplica la
   última como nuevo borrador, copiando frente y reverso completos).

### Campos dinámicos disponibles

`athlete_name`, `bib_number`, `event_name`, `event_date`, `race_name`,
`distance`, `official_time`, `pace`, `overall_position`, `category`,
`category_position`, `swim_time`, `bike_time`, `run_time`, `personal_phrase`,
`legacy_code`, `plate_serial` — catálogo completo en
`App\Support\PlateDynamicFields`.

> `swim_time`/`bike_time`/`run_time` no tienen todavía una columna en
> `event_results` — hoy solo se llenan en placas rápidas donde el operador
> los escribe manualmente. Placas integradas los dejan vacíos hasta que exista
> esa fuente de datos.

## 4. Versionado

Una versión **publicada nunca se edita**. Si hay que cambiar el logo o
corregir un texto después de haber impreso 300 placas con la V1, se crea la
V2 — las placas ya generadas siguen apuntando a `plate_template_version_id`
= V1 (columna en `plates`, ver `App\Models\Plate::plateTemplateVersion()`).
Nada reescribe esa referencia retroactivamente. Frente y reverso viven en la
**misma fila** (`front_configuration`/`back_configuration` en
`plate_template_versions`), así que no pueden desincronizarse en versión.

Publicar se bloquea (422) si el molde tiene `minimum_validated_qr_size_mm`
configurado (solo se pone después de una prueba física real — ver §10) y el
QR del reverso mide menos que eso.

Un molde tampoco se elimina nunca (no existe endpoint de borrado). Solo se
puede archivar (`active = false` en el molde, o `status = archived` en una
versión), lo que lo oculta de los selectores de "asignar a evento" sin
afectar placas históricas.

## 5. Asignar un molde a un evento

En `/admin/events/{edition}/production-setup`:

- Seleccionar una versión **publicada** como molde principal del evento.
- Opcionalmente, sobreescribir por distancia (21K, 42K, etc.) si el evento
  tiene carreras con moldes distintos.
- Si la edición está `in_progress`, cambiar el molde principal muestra una
  confirmación fuerte (puede provocar placas con diseños distintos dentro
  del mismo evento).
- Checklist de la pantalla: molde asignado, versión publicada, prueba física
  de QR realizada (usuario + fecha, es la única atestación manual — el resto
  se deriva de los datos existentes).
- Botones "Prueba de grabado" (frente/reverso) generan un SVG con datos demo
  fijos para calibrar la máquina antes del evento real, sin crear ninguna
  placa ni Legacy Code.

`App\Models\EventEdition::defaultPlateTemplateVersion($eventRaceId)` es la
única función que resuelve "qué versión le toca a esta placa" — la usan por
igual `PlateGenerationService` (al generar) y `OperatorController::previewPlate`
(al previsualizar antes de generar). `PlateGenerationService` **rechaza**
crear una placa (y su Legacy Code/ProductionJob) si esta función devuelve
null — nunca existe una placa sin molde.

## 6. Generar una placa

Todo pasa por `App\Services\PlateGenerationService` (dos entradas):

- **Integrada** (`generateIntegrated`): desde `/operator`, buscando un
  corredor existente. Usa sus datos de `EventParticipant`/`EventResult`.
- **Rápida** (`generateQuick`): sin corredor en base de datos — el operador
  escribe nombre y, opcionalmente, dorsal/tiempo/splits/frase personal.
  `user_id` queda `NULL`, pero el molde (ambas caras), el Legacy Code y el
  QR del reverso se generan exactamente igual que en el camino integrado.

Ambas, en una sola transacción: crean el `Plate` (snapshot de los datos +
`plate_template_version_id`), un `LegacyCode` permanente, y un
`ProductionJob` en cola. El preview que se muestra *antes* de confirmar usa
datos demo con Legacy Code `FL-PREVIEW` — nunca se crea un código real hasta
pulsar "Generar placa". La pantalla de éxito muestra el frente y el reverso
(tabs), dejando explícito que son dos grabados físicos distintos.

## 7. Exportar para producción

Desde el detalle de una placa (`/admin/plates/{id}`) o desde la pantalla de
éxito del operador: "Descargar para láser" abre un diálogo con:

- **Formato**: SVG (recomendado, vectorial, marcado explícitamente en la
  UI), PNG (300/600 DPI), PDF (tamaño físico exacto, no A4), o paquete ZIP
  completo (ambas caras).
- **Cara**: frente o reverso — selección independiente para SVG/PNG/PDF.
  El paquete ZIP siempre trae **ambas caras como archivos separados**
  (nunca mezcladas en un solo SVG).
- El paquete ZIP (`plate-{serial}/`) incluye: `front.svg`, `back.svg`,
  `front.png`, `back.png`, `front.pdf`, `back.pdf`, `qr.svg` y
  `production.json` (serial, Legacy Code, evento, molde, versión,
  dimensiones físicas, `back_transform`, caras incluidas — sin datos
  sensibles).

Nada se guarda en disco: cada descarga se regenera en el momento desde el
snapshot de la placa + la versión de molde exacta que usó. Descargar dos
veces produce el archivo idéntico byte a byte (mismo texto, mismo QR).

Para varias placas a la vez: en `/admin/plates`, seleccionar filas con el
checkbox y "Exportar lote" (tope configurable en
`config('plate-studio.batch_export_limit')`, 50 por defecto) — ZIP con
`{dorsal-o-serial}_{NOMBRE-SANITIZADO}_FRONT.svg` /
`..._BACK.svg` por placa (nunca el correo del atleta) más un
`manifest.json`. Sanitización en `App\Support\PlateFilename`.

## 8. Requisitos técnicos del SVG (para LightBurn)

- `width`/`height` siempre en milímetros reales (`width="60mm"`), nunca en
  píxeles como especificación física — el `viewBox` usa las mismas unidades.
- El QR es 100% vectorial (`App\Services\QrCodeService`, nunca un raster
  incrustado), con zona de silencio y negro puro sobre blanco/transparente
  según el modo de grabado.
- Los elementos `image`/`logo`/`icon` deben referenciar un asset embebible o
  accesible de forma portable — un SVG de producción no debe depender de
  `localhost` ni de una ruta `/storage/...` que solo existe en este servidor.
  Hoy ningún preset oficial usa `image` (evita el problema por diseño); si
  un admin agrega uno para un evento real, debe verificar que el archivo
  sea accesible desde la PC de producción.
- **Tipografías**: el SVG referencia la fuente por nombre (`Inter, Arial,
  sans-serif`) — no convierte texto a paths/outlines todavía. Esto es un
  riesgo real: si la PC de producción no tiene esa fuente instalada,
  LightBurn puede sustituirla y el resultado grabado no coincidirá con el
  preview web. **Mitigación actual:** asegurar Inter (o al menos Arial)
  instalada en la PC de producción. Convertir a paths en el export es
  trabajo futuro, documentado aquí, no implementado.

## 9. Perfil de máquina

`MachineProfile` es una etiqueta de flujo, no una integración — Finisher
Legacy no manda trabajos a la máquina ni controla USB. Sirve para que la UI
diga "Descargar para Fiber 30W — LightBurn" en vez de un genérico "Descargar
SVG". Seed de desarrollo: **Fiber 30W — LightBurn**, formato SVG, 60×40mm.

**No incluye potencia/velocidad/frecuencia** — esos valores dependen del
material, acabado, fuente y lente de cada máquina y se calibran físicamente
con una placa de prueba, nunca se envían como default universal. Un
"Material Profile" que sí capture esos valores queda como concepto futuro,
explícitamente no automatizado en esta fase.

### Transformación del reverso (`back_transform`)

Al voltear físicamente la placa en el jig, el reverso puede necesitar
`none` (default), `mirror_x`, `mirror_y` o `rotate_180` según el proceso —
columna `plate_templates.back_transform`
(`App\Enums\PlateBackTransform`). **Nunca se elige automáticamente**: se
determina con una prueba física y se guarda en el molde. El export SVG
todavía no aplica esta transformación por sí solo (hoy solo se registra y
se expone en `production.json`) — aplicarla al archivo exportado es trabajo
futuro.

### Jig / posicionador

Usa una plantilla física (jig) para colocar cada placa siempre en la misma
posición al grabar frente y reverso — sin eso, "voltear y grabar el
reverso alineado" no es repetible. Finisher Legacy no construye ni vende
hardware; esto es una recomendación de proceso.

## 10. Prueba física y calibración de QR

Antes de producción masiva: descarga el SVG de prueba de grabado (§5),
graba una placa sacrificial, escanea el QR. Si un tamaño de QR específico
queda validado (ej. 12mm), un admin puede guardarlo en
`plate_templates.minimum_validated_qr_size_mm`. **Nunca se fija un valor
por defecto** — mientras sea `null`, el renderer usa un umbral genérico de
advertencia (~10mm) sin bloquear publicación; una vez configurado, publicar
una versión con un QR más chico que ese mínimo se rechaza (§4).

Generador dedicado de "tarjeta de prueba" con múltiples tamaños de QR
(8/10/12/14mm) en una sola placa: **no implementado todavía** — hoy la
prueba de grabado exporta el molde real con datos demo, una cara a la vez.

## 11. Reimpresión

Desde el detalle de la placa, "Reimprimir" (solo si está `ready` o
`delivered`): pide un motivo y si se debe mantener el snapshot original o
actualizarlo con el resultado actual del corredor (si cambió después de
generada la placa — se muestra la comparación "grabado en la placa" vs.
"resultado actual").

**El Legacy Code nunca cambia en una reimpresión**, y ambas caras se
reexportan desde la **misma** `plate_template_version_id` original — nunca
se reasigna a una versión más nueva del molde. Se crea un nuevo
`ProductionJob` y un registro en `plate_reprints` para auditoría.

## 12. Producción física y escaneo

`/production` (kanban): pendiente → en proceso → lista → entregada. Cada
tarjeta tiene un ícono de descarga rápida que usa el formato/DPI por
defecto configurado en la edición del evento
(`event_editions.production_export_format` / `default_dpi`), para no tener
que abrir el diálogo completo durante un evento en vivo.

Después de entregada, el atleta escanea el **QR del reverso** →
`/l/{code}` → login/registro si no tiene cuenta → confirma "Vincular a mi
Legacy" → el mismo código de siempre, nunca uno nuevo.

## 13. Permisos

| Rol | Puede |
|---|---|
| `super_admin` / `admin` | Todo. |
| `event_manager` | Ver moldes (`platetemplates.view`), asignar molde a sus eventos (`editions.manage`). |
| `event_operator` | Generar placas, ver y descargar archivos (`plates.view`/`plates.manage`) — **no** puede editar moldes publicados. |
| `production_operator` | Ver placas, gestionar producción, descargar archivos. |
| `athlete` | Sin acceso a ningún panel de administración. |

## 14. URLs de prueba

- Plate Studio: `/admin/plate-studio`
- Preparar evento para producción: `/admin/events/{edition}/production-setup`
- Generar placa (integrada/rápida): `/operator`
- Placas (admin): `/admin/plates`
- Producción: `/production`
- Página pública permanente: `/l/{code}`

## 15. Lo que falta para producción real

- Convertir texto a paths/outlines en el export de producción (§8) — hoy
  depende de tener la fuente instalada en la PC de producción.
- Aplicar `back_transform` automáticamente al SVG exportado (§9) — hoy solo
  se registra como metadata.
- Primitivo vectorial de icono/path real para la iconografía temática del
  reverso (§2) — hoy se simula con texto y líneas.
- Generador de tarjeta de prueba con múltiples tamaños de QR en una sola
  placa (§10).
- Checklist de "frente grabado / reverso grabado / QR probado" por placa en
  `/production` — hoy el kanban solo tiene el estado general
  (pendiente/proceso/lista/entregada).
- UI de administración para `MachineProfile` (hoy solo existe el modelo +
  seed; se referencia por convención, no se gestiona desde el admin).
- Cola asíncrona para lotes de exportación más grandes que el límite
  configurado (hoy es síncrono con tope duro).
- Cache de miniaturas de preview (`preview_front_path`/`preview_back_path`
  existen en el esquema pero no se usan; el preview siempre se renderiza
  SVG en vivo).
- Integración directa con el software/driver de la máquina láser — esta
  fase termina en el archivo de exportación (SVG/PNG/PDF); el operador lo
  importa manualmente a LightBurn.
