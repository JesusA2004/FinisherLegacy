# ADR 0005 — Ingestión unificada de eventos externos

Estado: Aceptado
Fecha: 2026-08-23
Contexto previo: ADR 0002 (Device API, Slice 1), ADR 0003 (máquina de
estados de producción, Slice 2), ADR 0004 (identidad canónica de atleta,
Slice 3)

## Decisión

Toda fuente de datos de un evento — CSV/XLSX, una API externa por polling,
un webhook futuro, o alta manual — converge en **una sola** capa de
ingestión. Ninguna fuente decide cómo se guarda Finisher Legacy: cada
adapter traduce su formato nativo a los mismos DTOs canónicos
(`App\Support\Integrations\External*Data`), y esos DTOs son lo único que
la capa de aplicación conoce.

```
SOURCE                          ADAPTER                    CANONICAL DTO
CSV/XLSX          ──┐
API Provider (mock) ──┼──>  EventProviderAdapter   ──>  External*Data
Webhook (futuro)     ──┘        (App\Contracts\Integrations)

CANONICAL DTO
     │
     ▼
App\Services\Integrations\EventIngestionService   (orquestador delgado)
     │
     ├──> App\Actions\Athletes\IngestEventParticipant   (Slice 3, sin tocar)
     │         │
     │         ▼
     │    App\Actions\Athletes\ResolveAthleteIdentity   (Slice 3, sin tocar)
     │
     └──> App\Actions\Integrations\IngestEventResult
               │
               ▼
          EventResult + EventResultSplit (upsert idempotente)
```

## 1. Principio central

Una fuente nunca decide el shape de la persistencia. Ejemplo: un proveedor
llama `runner_number`, otro `bib`, otro `dorsal` — todos se normalizan a
`ExternalParticipantData::$bibNumber` antes de llegar a cualquier Action.
Ningún adapter devuelve un modelo Eloquent; solo DTOs (§14 del prompt
original).

## 2. Un solo pipeline, no N

`App\Actions\Athletes\IngestEventParticipant` (Slice 3) sigue siendo la
única puerta para crear/actualizar un `EventParticipant` y resolver su
identidad — CSV y API sync la llaman igual, nunca reimplementada. Lo nuevo
en Slice 4 es todo lo que hay *antes* (adapters + DTOs) y una pieza nueva
*después* para resultados: `App\Actions\Integrations\IngestEventResult`,
el equivalente de `IngestEventParticipant` para `EventResult` +
`EventResultSplit`. `App\Services\Integrations\EventIngestionService` es
el orquestador delgado que ambas fuentes llaman — nunca un "God Service":
resuelve la carrera (`resolveRace`) y el participante de un resultado
(`resolveParticipantForResult`), y delega el resto.

CSV/XLSX (`App\Imports\ParticipantsImport`) construye
`ExternalParticipantData`/`ExternalResultData` desde la fila y llama
exactamente estas mismas piezas — nunca tuvo, y sigue sin tener, su propio
upsert. La única diferencia real es que CSV ya conoce el `EventParticipant`
de la fila que acaba de procesar, así que para el resultado llama
`IngestEventResult` directo en vez de pasar por
`EventIngestionService::ingestResult()` (que existe para resolver el
participante *cuando no se conoce de antemano* — el caso de un sync de
resultados por API).

## 3. Provider contract y capability methods

`App\Contracts\Integrations\EventProviderAdapter`: `testConnection`,
`listEvents`, `fetchEvent`, `fetchParticipants`, `fetchResults`,
`supportsIncrementalSync`, `supportsWebhooks`. Ningún adapter está
obligado a soportar todo — los métodos `supports*` son la forma en que un
caller pregunta antes de asumir. `App\Contracts\Integrations\VerifiesWebhooks`
es una interfaz aparte y opcional: solo un adapter con
`supportsWebhooks() === true` la implementa. **No existe ninguna ruta de
webhook pública todavía** — la Slice 4 entrega el contrato, no un endpoint
real sin verificación de firma detrás (nunca confiar JSON crudo).

`App\Services\Integrations\EventProviderRegistry` resuelve `provider_key`
→ clase adapter. Hoy solo `mock` está registrado. Añadir un proveedor real
más adelante es una línea en `ADAPTERS`, no un `switch` en un controller.

## 4. DTOs canónicos

`App\Support\Integrations\`: `ExternalEventData`, `ExternalRaceData`,
`ExternalParticipantData`, `ExternalResultData`, `ExternalSplitData`,
`ExternalPage` (una página de resultados paginados: `items` + `nextCursor`
+ `hasMore` — nunca `collect()->all()` de una respuesta completa, ver §11),
`ProviderConnectionTestResult`. `externalParticipantId` es **nullable** en
`ExternalParticipantData` — a diferencia del resto de los IDs, un CSV
legítimamente no tiene uno (§76): la identidad de esa participación cae al
fallback `event_edition_id` + `bib_number`, que es exactamente la clave que
`IngestEventParticipant` ya usaba antes de Slice 4.

## 5. Mock Event Provider

`App\Services\Integrations\Providers\MockEventProviderAdapter`: 1 evento,
2 carreras (21K/42K), 100 participantes deterministas (sin `rand()`/estado
mutable propio). El "reloj" del evento en vivo — 0 → 20 → 55 → 100
finishers — vive enteramente en `ProviderConnection::settings.mock_finishers_count`,
nunca en el adapter; esto es lo que permite que el adapter sea seguro de
resolver como singleton y que `php artisan finisher:simulate-live-event`
simule un evento en vivo con solo actualizar esa una columna entre syncs.

## 6. Identity resolution — reutilizada, no reinventada

`EventIngestionService::ingestParticipant()` construye el array de
atributos que `IngestEventParticipant` ya esperaba y le añade
`external_participant_id`; la resolución de identidad (matcher,
conflictos, `AthleteExternalIdentity`) es exactamente la de ADR 0004, sin
tocar. Un `external_athlete_id` que ya apareció en otro evento —
`AthleteExternalIdentity` con `reason = ExternalIdentityExact` (confianza
100, prioridad más alta del matcher) — hace que la segunda participación
resuelva al mismo `Athlete`, cumpliendo el criterio §105 sin ninguna lógica
nueva de matching.

## 7. Provider connections, mappings, provenance

- **`provider_connections`**: la conexión (credenciales, base URL, estado)
  — nunca la implementación, que es código resuelto por `provider_key` vía
  el registry. `credentials` usa cast `encrypted`, `settings` usa
  `encrypted:array`; `credentials` está en `$hidden` del modelo — nunca
  sale en un response ni en un log.
- **`external_event_mappings`**: `(provider_connection_id, external_event_id)`
  único → `event_edition_id`. Solo se crea al vincular, nunca al listar —
  sincronizar el mismo evento externo N veces sigue resolviendo al mismo
  `EventEdition` (§23, probado en `SyncExternalEventIdempotencyTest`).
- **`external_race_mappings`**: igual, por carrera — creado automáticamente
  en el primer sync de roster desde `ExternalEventData::$races`
  (`SyncExternalParticipants::ensureRaceMappings()`), nunca a mano.
- **`external_participant_mappings`**: separado de
  `event_participants.external_participant_id` porque un mismo evento
  puede tener roster de un proveedor y resultados de otro (§94) — una sola
  columna no puede cargar dos IDs externos distintos para la misma fila.
  También guarda `external_athlete_id`, para resolver un resultado que
  trae ese dato sin tener que pasar por `Athlete` primero.
- **Decisión documentada (§61 del prompt original)**: no existe una tabla
  `external_result_mappings` separada. `EventResult` ya es 1:1 con
  `EventParticipant` (`event_participant_id` único) — el participant
  mapping es suficiente para llegar al resultado correcto sin una tabla
  adicional.

## 8. Sync run + errores — observabilidad bulk, no un log por fila

`ExternalSyncRun` (contadores + `status`: `pending/running/completed/partial/failed`)
es lo que la UI de admin lee para el dashboard de sincronización — nunca
un `ActivityLog` por participante (§85, sería millones de filas en un
evento de 50k). Una fila mala nunca tumba las 9,999 buenas: se captura por
ítem y se registra en `ExternalSyncError` (`code`, `message`, nunca
credenciales ni el payload crudo completo). Solo un error de *conexión*
(`ProviderUnavailableException`/`ProviderRateLimitedException`, o
cualquier excepción no capturada por fila) marca el run entero `Failed`.

`App\Actions\Integrations\SyncExternalEvent` es el orquestador: abre el
run, llama al adapter, delega a `SyncExternalParticipants`/
`SyncExternalResults` (que a su vez paginan con `ExternalPage` y nunca
cargan el roster completo en memoria — chunk configurable vía
`settings.chunk_size`, default 250), persiste el cursor incremental en el
mapping, y cierra el run. `App\Jobs\SyncExternalEventJob` es lo único que
"Sincronizar ahora" en el admin dispatcha — nunca una llamada HTTP al
proveedor dentro del ciclo request/response — con `WithoutOverlapping`
por mapping para que un sync en vivo programado nunca se apile sobre uno
manual todavía corriendo.

## 9. Correcciones manuales — nunca pisadas por el próximo sync

`event_results` ganó `manual_override_at/by/fields` (json, lista de
columnas bloqueadas). `IngestEventResult` nunca escribe un campo que
aparezca en `manual_override_fields` — un admin corrigiendo
`official_time` no bloquea `pace` de paso. La UI para marcar/desmarcar un
override queda para cuando exista una pantalla de edición de resultados
(no existe hoy; el override es reconocido y respetado por el pipeline, la
gestión desde UI es deuda de Slice 5+).

## 10. Plate eligibility

`App\Services\PlateEligibilityService::check()` centraliza "¿puede esta
participación producir una Plate integrada ahora?" con razones explícitas
(`NO_RESULT`, `NO_TEMPLATE`, `IDENTITY_CONFLICT`, `PLATE_ALREADY_EXISTS`).
Deliberadamente **nunca produce nada** — ningún sync dispara producción
automática de miles de placas (§121-123); un operador siempre decide.

## 11. Lo que Slice 4 NO hace

- Ningún proveedor real conectado — solo Mock. `GenericRestEventProviderAdapter`
  queda para cuando exista un proveedor real que lo justifique (§51: mock
  primero, generalizar después, nunca un mapeador JSON universal
  especulativo).
- Ningún endpoint de webhook público — solo el contrato
  (`VerifiesWebhooks`).
- Ninguna UI de edición de resultados con toggle de "bloquear campo" — el
  override se respeta en el pipeline; falta la pantalla para activarlo
  manualmente desde el admin.
- Ningún reintento/backoff HTTP real conectado a un adapter — no hay
  todavía un adapter que haga HTTP real. Queda documentado como
  infraestructura a construir junto con el primer `GenericRestEventProviderAdapter`.
- Prioridad configurable multi-provider por evento (roster de A, resultados
  de B simultáneos con reglas de "quién gana") — el schema lo permite
  (`external_*_mappings` no son únicos por evento, solo por
  `provider_connection_id` + id externo), pero no hay UI ni lógica de
  resolución de conflicto entre dos providers activos sobre el mismo
  evento todavía.
