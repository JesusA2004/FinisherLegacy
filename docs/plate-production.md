# Producción de placas: de molde a placa entregada

Este documento describe el flujo completo de Plate Studio: cómo se diseña un
molde, cómo se asigna a un evento, cómo se genera una placa real, y cómo se
exporta el archivo que va a la máquina de grabado láser. Ver también
`docs/legacy-code-lifecycle.md` para la regla de permanencia del Legacy Code.

## 1. Conceptos

| Concepto | Qué es | Dónde vive |
|---|---|---|
| **Plate Template** (Molde) | Metadatos de un diseño de placa: nombre, dimensiones físicas (mm), material, orientación, margen de seguridad. | `plate_templates` / `App\Models\PlateTemplate` |
| **Plate Template Version** | Una revisión concreta del diseño: configuración de frente y reverso (lista de elementos), estado (`draft`/`published`/`archived`). | `plate_template_versions` / `App\Models\PlateTemplateVersion` |
| **Event Plate Template** | Qué versión de molde usa una edición de evento (o una carrera específica dentro de ella) por defecto. | `event_plate_templates` / `App\Models\EventPlateTemplate` |
| **Plate** | Una placa concreta para una persona: snapshot de sus datos en el momento de generarla, más la versión de molde usada. | `plates` / `App\Models\Plate` |
| **Legacy Code** | Identidad permanente de la placa. Ver `docs/legacy-code-lifecycle.md`. | `legacy_codes` / `App\Models\LegacyCode` |
| **Production Job** | Un trabajo en el kanban de `/production`. Una placa puede tener varios a lo largo de su vida (reimpresiones). | `production_jobs` / `App\Models\ProductionJob` |

El **renderer único** (`App\Services\PlateTemplateRenderService`) es lo que
convierte una versión de molde + los datos de una placa en SVG. El export a
PNG (`GdPlateRenderer`) y PDF (`PdfExportService`) parten de la misma lista de
elementos resuelta por ese servicio — nunca se recalculan posiciones ni
auto-fit por separado para cada formato.

## 2. Crear un molde

1. Entrar a `/admin/plate-studio` (permiso `platetemplates.manage`).
2. "Nuevo molde": nombre, ancho/alto en mm, orientación, margen de seguridad.
   Se crea el `PlateTemplate` y su primera versión en estado `draft`.
3. En el editor: pestañas **Frente** / **Reverso**, agregar elementos desde
   la barra lateral (texto estático, campo dinámico, QR, línea, rectángulo,
   imagen, serial). Arrastrar para posicionar, panel derecho para ajustar
   tamaño/color/alineación exactos.
4. El toggle **Vista producto / Vista grabado** cambia entre los colores
   configurados y blanco/negro puro (lo que realmente se graba).
5. "Guardar" (Ctrl/Cmd+S) guarda el borrador. "Publicar" congela la versión
   — ya no se puede editar; para cambiar el diseño hay que crear una nueva
   versión ("Nueva versión" duplica la última como nuevo borrador).

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

## 3. Versionado

Una versión **publicada nunca se edita**. Si hay que cambiar el logo o
corregir un texto después de haber impreso 300 placas con la V1, se crea la
V2 — las placas ya generadas siguen apuntando a `plate_template_version_id`
= V1 (columna en `plates`, ver `App\Models\Plate::plateTemplateVersion()`).
Nada reescribe esa referencia retroactivamente.

Un molde tampoco se elimina nunca (no existe endpoint de borrado). Solo se
puede archivar (`active = false` en el molde, o `status = archived` en una
versión), lo que lo oculta de los selectores de "asignar a evento" sin
afectar placas históricas.

## 4. Asignar un molde a un evento

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
(al previsualizar antes de generar).

## 5. Generar una placa

Todo pasa por `App\Services\PlateGenerationService` (dos entradas):

- **Integrada** (`generateIntegrated`): desde `/operator`, buscando un
  corredor existente. Usa sus datos de `EventParticipant`/`EventResult`.
- **Rápida** (`generateQuick`): sin corredor en base de datos — el operador
  escribe nombre y, opcionalmente, dorsal/tiempo/splits/frase personal.

Ambas, en una sola transacción: crean el `Plate` (snapshot de los datos +
`plate_template_version_id`), un `LegacyCode` permanente, y un
`ProductionJob` en cola. El preview que se muestra *antes* de confirmar usa
datos demo con Legacy Code `FL-PREVIEW` — nunca se crea un código real hasta
pulsar "Generar placa".

## 6. Exportar para producción

Desde el detalle de una placa (`/admin/plates/{id}`) o desde la pantalla de
éxito del operador: "Descargar para láser" abre un diálogo con:

- **Formato**: SVG (recomendado, vectorial), PNG (300/600 DPI), PDF (tamaño
  físico exacto, no A4), o paquete ZIP completo.
- **Cara**: frente o reverso.
- El paquete ZIP incluye `front.svg`, `back.svg`, `qr.svg` y
  `metadata.json` (serial, Legacy Code, evento, molde, versión, atleta,
  fecha de generación — sin datos sensibles adicionales).

Nada se guarda en disco: cada descarga se regenera en el momento desde el
snapshot de la placa + la versión de molde exacta que usó. Descargar dos
veces produce el archivo idéntico byte a byte (mismo texto, mismo QR).

Para varias placas a la vez: en `/admin/plates`, seleccionar filas con el
checkbox y "Exportar lote" (tope configurable en
`config('plate-studio.batch_export_limit')`, 50 por defecto) — ZIP con
`{serial}-front.svg`/`{serial}-back.svg` por placa más un `manifest.json`.

## 7. Reimpresión

Desde el detalle de la placa, "Reimprimir" (solo si está `ready` o
`delivered`): pide un motivo y si se debe mantener el snapshot original o
actualizarlo con el resultado actual del corredor (si cambió después de
generada la placa — se muestra la comparación "grabado en la placa" vs.
"resultado actual").

**El Legacy Code nunca cambia en una reimpresión.** Se crea un nuevo
`ProductionJob` y un registro en `plate_reprints` para auditoría; el
`legacy_code_id` de la placa es el mismo de siempre.

## 8. Producción física

`/production` (kanban): pendiente → en proceso → lista → entregada. Cada
tarjeta tiene un ícono de descarga rápida que usa el formato/DPI por
defecto configurado en la edición del evento
(`event_editions.production_export_format` / `default_dpi`), para no tener
que abrir el diálogo completo durante un evento en vivo.

## 9. Permisos

| Rol | Puede |
|---|---|
| `super_admin` / `admin` | Todo. |
| `event_manager` | Ver moldes (`platetemplates.view`), asignar molde a sus eventos (`editions.manage`). |
| `event_operator` | Generar placas, ver y descargar archivos (`plates.view`/`plates.manage`) — **no** puede editar moldes publicados. |
| `production_operator` | Ver placas, gestionar producción, descargar archivos. |
| `athlete` | Sin acceso a ningún panel de administración. |

## 10. URLs de prueba

- Plate Studio: `/admin/plate-studio`
- Preparar evento para producción: `/admin/events/{edition}/production-setup`
- Generar placa (integrada/rápida): `/operator`
- Placas (admin): `/admin/plates`
- Producción: `/production`
- Página pública permanente: `/l/{code}`

## 11. Lo que falta para producción real

- Fuente Inter embebida en PNG/PDF (hoy usa Arial del sistema como
  alternativa documentada — ver `config/plate-studio.php`).
- Cola asíncrona para lotes de exportación más grandes que el límite
  configurado (hoy es síncrono con tope duro).
- Cache de miniaturas de preview (`preview_front_path`/`preview_back_path`
  existen en el esquema pero no se usan; el preview siempre se renderiza
  SVG en vivo).
- Integración directa con el software/driver de la máquina láser — esta
  fase termina en el archivo de exportación (SVG/PNG/PDF); el operador lo
  importa manualmente al software de su máquina.
