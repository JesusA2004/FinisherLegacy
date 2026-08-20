# ADR 0004 — Identidad canónica de atleta y deduplicación entre eventos

Estado: Aceptado
Fecha: 2026-08-22
Contexto previo: ADR 0001 (baseline), ADR 0002 (Device API, Slice 1), ADR 0003
(máquina de estados de producción, Slice 2)

## Decisión

Se introduce `App\Models\Athlete` como la **persona real**, distinta de
`User` (la cuenta) y de `EventParticipant` (la inscripción/snapshot en un
evento). Un mismo ser humano corriendo 3 maratones bajo 3 bibs distintos
debe resolver a **1 solo `Athlete`** con 3 filas `EventParticipant`
enlazadas — el número de bib nunca identifica a una persona globalmente,
solo una participación.

```
User (0..1) ── belongsTo ──> Athlete (1) ── hasMany ──> EventParticipant (N)
                                  │                            │
                                  ├── hasMany ──> Plate (N)     └─> EventResult
                                  ├── hasMany ──> Medal (N)
                                  └── hasMany ──> AthleteExternalIdentity (N)
```

Ver también `docs/architecture/athlete-identity.md` para el diagrama
completo con `AthleteProfile` y `LegacyCode`.

## 1. Modelo de propiedad — quién es dueño de qué (§1-9)

- **`User`**: la cuenta (login, password, roles). No es la identidad de la
  persona; puede no tener ninguna, o (rarísimo, ver §Deuda) compartir
  cuenta con más de un contexto.
- **`Athlete`**: la persona real, canónica, deduplicada. Vive
  independientemente de si hay o no una cuenta — un `Athlete` puede existir
  solo a partir de datos de importación CSV, sin `User` nunca.
- **`AthleteProfile`** (Slice 0, sin tocar): la ficha pública/de
  presentación, siempre propiedad de un `User` (username, bio, redes). No
  se fusiona con `Athlete` — un `Athlete` puede no tener perfil público y un
  perfil público implica siempre un `User`, nunca al revés.
- **`EventParticipant`**: sigue siendo el snapshot inmutable de cómo se
  llamaba/qué datos tenía la persona **en ese evento** (igual que antes de
  Slice 3 — nunca se sobrescribe con datos "actuales" del Athlete). Ahora
  además carga `athlete_id` nullable: la liga a la identidad canónica sin
  tocar el snapshot.
- **`Plate` / `Medal`**: ambos ganan `athlete_id` nullable, aditivo, en
  paralelo a su `user_id` existente — nunca lo reemplaza. `user_id` sigue
  significando "quién tiene la cuenta que puede ver/reclamar esto";
  `athlete_id` significa "de qué persona canónica es esto", y puede estar
  poblado sin que haya `user_id` (medalla/placa de alguien sin cuenta
  todavía).
- **`LegacyCode`**: sin cambios — sigue enlazado a `User`, no a `Athlete`.
  El claim de un LegacyCode es lo que dispara la resolución de identidad
  (ver §Claim abajo), pero el código en sí sigue siendo un artefacto de
  cuenta, no de persona.

La relación `User::athlete()` (`HasOne`) es deliberadamente distinta de
`User::athleteProfile()`: la primera es identidad canónica (puede no
existir), la segunda es presentación pública (siempre de este User).

## 2. Normalización de identidad — funciones puras (§10-16)

`App\Support\Athletes\AthleteIdentityNormalizer` es puro, sin acceso a BD,
sin dependencias de framework más allá de `Str`:

- **Nombre**: `trim` + colapsar espacios múltiples + quitar acentos
  (`Str::ascii()`) + minúsculas. "José María Ñúñez" → "jose maria nunez".
  Nunca reordena palabras ni intenta separar apellidos compuestos.
- **Email**: `trim` + minúsculas únicamente. Deliberadamente **no** quita
  puntos de Gmail ni tags `+algo` — normalizar de más ahí produciría falsos
  positivos entre personas distintas que comparten un patrón de correo
  corporativo o educativo.
- **Teléfono**: quita todo lo que no sea dígito, conserva un `+` inicial si
  el original lo tenía. Nunca asume un código de país por default — un
  teléfono sin `+` normalizado no se compara contra uno con `+` de forma
  ciega (eso lo decide el matcher, no el normalizador).

Estas funciones no deciden si dos personas son la misma — solo producen la
forma canónica de un dato. La decisión de "son la misma persona" vive
exclusivamente en el matcher (§3).

## 3. Matcher determinista — sin ML (§17-22)

`App\Services\Athletes\AthleteIdentityMatcher`. Tabla de razones y
confianza (`App\Enums\AthleteMatchReason`):

| Razón | Confianza | Descripción |
|---|---|---|
| `ExternalIdentityExact` | 100 | Coincidencia exacta en `athlete_external_identities` (provider + connection + subject) |
| `VerifiedEmailAndBirthdate` | 100 | Email verificado (`email_verified_at` no nulo) + fecha de nacimiento iguales |
| `EmailExact` | 95 | Email normalizado igual, sin verificar |
| `PhoneAndBirthdate` | 95 | Teléfono normalizado + fecha de nacimiento iguales |
| `NameAndBirthdate` | 80 | Nombre completo normalizado + fecha de nacimiento iguales |
| `NameOnly` | 30 | Solo nombre completo normalizado igual |

**Regla absoluta (§20, no negociable):** nombre solo (`NameOnly`, 30) nunca
puede disparar auto-merge — está por debajo del umbral de conflicto
(60), así que resuelve directo a `NoMatch`. Esto es una desviación
deliberada de una lectura literal del §19 original, que sugería que
coincidencias de nombre repetidas también deberían generar conflicto: con
nombres comunes (p. ej. "Juan Pérez") eso inundaría la cola de
`/admin/identity-conflicts` de falsos positivos sin valor. El umbral se
fijó así intencionalmente y queda documentado aquí para no revisitarlo sin
razón.

**Umbrales de decisión** (constantes en el matcher):

- `AUTO_LINK_THRESHOLD = 95` — con exactamente 1 candidato a esa confianza
  o más, se enlaza automáticamente, sin intervención humana.
- `CONFLICT_THRESHOLD = 60` — por debajo, `NoMatch` (se crea un `Athlete`
  nuevo). Entre 60 y 94 inclusive, o múltiples candidatos empatados a
  cualquier confianza ≥60, se registra un `AthleteIdentityConflict` para
  revisión humana — nunca se auto-decide.

Orden de evaluación: identidad externa exacta primero (retorno inmediato,
100), luego email+nacimiento verificado, luego email solo, luego
teléfono+nacimiento, luego nombre+nacimiento, y nombre-solo siempre corre
al final como fallback (nunca detrás de un `if`, para no dejar un
candidato sin evaluar).

## 4. Conflictos de identidad — revisión humana, no automática (§23-30)

`App\Models\AthleteIdentityConflict`: guarda `incoming_data` (snapshot
completo de lo que llegó), `candidates` (lista completa rankeada, para el
caso "nombre ambiguo" con más de un candidato empatado), `confidence`,
`reason`, y `status` (`Pending`/`Resolved`/`Ignored`).

`/admin/identity-conflicts` muestra **bandas** de confianza (Alta ≥80,
Media ≥60, Baja/sin dato) — nunca el porcentaje crudo como si fuera
certeza matemática (§85: mostrar "92%" da una falsa sensación de precisión
que el modelo no tiene). Tres acciones, vía
`App\Actions\Athletes\ResolveAthleteIdentityConflict`:

1. **Es la misma persona** (`link_existing`) — enlaza al `Athlete`
   candidato elegido.
2. **Crear nuevo atleta** (`create_new`) — el humano decide que NO son la
   misma persona pese a la similitud; se crea un `Athlete` nuevo vía
   `CreateAthlete` (la única puerta de entrada para crear Athletes, ver
   §5).
3. **Ignorar por ahora** (`ignore`) — deja el `EventParticipant` sin
   `athlete_id`, marca el conflicto `Ignored`. Puede retomarse después; no
   bloquea nada más del sistema.

## 5. Un solo pipeline compartido (§31-38)

`App\Actions\Athletes\ResolveAthleteIdentity` es el **único** punto de
entrada para resolver "¿a qué Athlete pertenece este dato entrante?" —
CSV import, registro de cuenta, claim de Legacy Code y backfill retroactivo
llaman exactamente a esta Action, nunca implementan su propio matching.
Internamente: matcher → `Matched`/`Created`/`Conflict`. `CreateAthlete` es
la única clase que llama `Athlete::create()` fuera de factories/tests —
nadie más construye un Athlete a mano.

Para evitar una carrera donde dos requests concurrentes con el mismo email
creen dos Athletes distintos, `ResolveAthleteIdentity` toma un
`Cache::lock("athlete-identity:{email}")` (driver `database`, sin Redis)
cuando hay email normalizado; sin email (solo nombre) no hace falta lock
porque nombre-solo nunca auto-crea de forma ambigua sin pasar por
conflicto.

## 6. Identidades externas (§39-40, fuera de alcance real de Slice 3)

`athlete_external_identities` existe con el schema completo
(`provider`, `provider_connection_id`, `external_subject_id`) pero **sin
ningún proveedor real conectado todavía** — es la puerta para Slice 4
(Google/Facebook/Apple login u otro IdP). `provider_connection_id` usa
`''` como default en vez de `null` porque MySQL trata cada `NULL` como
distinto dentro de un índice único compuesto — con `null` el índice
`(provider, provider_connection_id, external_subject_id)` no habría
evitado duplicados reales.

## 7. Integración por transporte (§41-74)

- **Import CSV** (`App\Imports\ParticipantsImport`): `importRow()` ahora
  llama `App\Actions\Athletes\IngestEventParticipant`, que hace el
  `updateOrCreate` de siempre (misma clave `event_edition_id`+
  `bib_number`, sin cambios) y luego resuelve identidad. Un conflicto
  **nunca** hace fallar la fila del import — el participante se guarda con
  `athlete_id` nulo y el conflicto queda pendiente en el admin.
- **Registro de cuenta** (`app/Actions/Fortify/CreateNewUser.php`): tras
  emitir el LegacyCode de siempre, llama
  `App\Actions\Athletes\EnsureAthleteForUser::handle($user, 'registration')`.
- **Alta manual de usuario admin** (`Admin\UserController::store()` /
  `updateRoles()`): mismo `EnsureAthleteForUser`, cuando se asigna el rol
  `athlete`.
- **"Alta manual de participante" y "conversión de preregistro"** (§73-74
  del prompt original): **no existen como flujos de código reales en esta
  app hoy** — `Admin\ParticipantController` es de solo lectura y los
  preregistros solo se *matchean* contra un participante ya existente,
  nunca se *convierten* en uno. No se inventó código muerto para simular
  esos flujos; cuando existan, deberán llamar al mismo
  `ResolveAthleteIdentity` que todo lo demás.

## 8. Claim de Legacy Code — la regla crítica (§75-84)

`App\Services\ClaimLegacyCodeService::resolveClaimAthlete()`:

1. Se calcula `$participantAthlete` (el Athlete ya enlazado al
   `EventParticipant` de la placa, si lo hay) y `$userAthlete` (el Athlete
   ya enlazado al `User` que reclama, si lo hay).
2. **Ambos existen y son distintos** → se crea un
   `AthleteIdentityConflict` (`source_type = 'claim'`,
   `reason = 'claim_mismatch'`) y se lanza
   `AthleteIdentityConflictException`, que **aborta toda la transacción del
   claim** — nada se persiste. Nunca se auto-decide cuál de los dos gana.
3. Solo existe `$participantAthlete` → se enlaza el `User` a ese Athlete
   (`LinkUserToAthlete`).
4. Solo existe `$userAthlete` → se usa directamente (la placa/participante
   se enlazan a él).
5. Ninguno existe → `EnsureAthleteForUser` (mismo camino que registro).

Tanto claim integrado como claim rápido ("quick plate", sin
`EventParticipant`) pasan por el mismo método — la única diferencia es que
un quick plate nunca tiene `$participantAthlete` porque no hay
`EventParticipant` de por medio, así que cae directo al caso 3/4/5 según
si el usuario ya tenía Athlete.

`PlateGenerationService::generateQuick()` deliberadamente **no** toca
`athlete_id` — se queda `null` hasta el claim, que es cuando por fin hay un
`User` con quien resolver identidad. `generateIntegrated()` sí puebla
`athlete_id` desde `$participant->athlete_id` al crear la placa, porque ahí
ya existe el snapshot del participante.

## 9. Fusión de atletas (§75-84 continuación)

`App\Actions\Athletes\MergeAthletes::handle(source, target, actor, reason)`:
transacción con `lockForUpdate()` en ambas filas. **Bloqueado por
construcción** (`AthleteMergeUserConflictException`) si `source` y `target`
tienen `user_id` distintos y ambos no nulos — fusionar ahí borraría la
cuenta de alguien sin consentimiento humano explícito fuera de este flujo.
Mueve `EventParticipant`/`Plate`/`Medal` de `source` a `target`, mueve
identidades externas (con el mismo chequeo de conflicto por si `target` ya
tiene una igual), mueve `user_id` si `target` no tenía. `source` queda
`identity_status = Merged`, `merged_into_athlete_id = target.id`,
`user_id = null` — nunca se borra la fila, por trazabilidad y para que
cualquier referencia vieja (incluyendo el propio historial de ActivityLog)
siga resolviendo a algo real.

## 10. Backfill retroactivo (§85 numeración del prompt / comando real)

`php artisan finisher:backfill-athletes {--dry-run|--apply}` — exactamente
uno de los dos flags, nunca ambos ni ninguno. Orden de prioridad:

1. `User`s con rol `athlete` sin `Athlete` todavía.
2. `EventParticipant`s con `user_id` conocido pero `athlete_id` nulo (usan
   el Athlete de su User, creándolo si hace falta).
3. Todo el resto de `EventParticipant`s sin `athlete_id`, resuelto por el
   mismo matcher que import/registro/claim — **nunca** una pasada más
   agresiva solo porque es "backfill".

Idempotente: una segunda corrida sobre el mismo dataset produce los mismos
conteos, porque el paso 3 excluye explícitamente participantes con un
`AthleteIdentityConflict` `Pending` de una corrida anterior (se quedan
pendientes hasta que un humano los resuelva en el admin, nunca se
reintenta el matching automáticamente sobre ellos). `--dry-run` nunca
escribe: llama al matcher crudo, no a `ResolveAthleteIdentity`, y solo
tabula lo que habría pasado.

## 11. UI de administración — no es un CRM (§85-99)

`/admin/athletes` (lista, búsqueda por nombre/email/legacy ID) y
`/admin/athletes/{id}` (detalle: participaciones por evento — la prueba
visual de "1 Athlete, N eventos" —, placas, medallas). Deliberadamente sin
edición de datos del Athlete desde ahí ni gestión de campos libres: es una
vista de auditoría/soporte, no un CRM de gestión de atletas.
`/admin/identity-conflicts` es la única superficie de acción real
(resolver conflictos), protegida por permisos dedicados
(`athletes.view`, `athletes.manage`).

## 12. Lo que Slice 3 NO hace todavía

- Ningún proveedor de identidad externa real conectado (`Slice 4`) — solo
  el schema.
- Ninguna fusión automática por confianza alta sin pasar por
  `/admin/identity-conflicts` cuando hay ambigüedad — toda fusión real la
  dispara un humano vía `MergeAthletes`, nunca el matcher por sí solo.
- Reindexación en background de Athletes existentes cuando cambian las
  reglas de normalización — un cambio futuro a
  `AthleteIdentityNormalizer` requeriría re-correr el backfill
  manualmente, no hay un job automático para eso.
- Deduplicación de `User`s que ya comparten email pero fueron creados
  antes de Slice 3 sin pasar por este pipeline — el backfill los cubre
  retroactivamente, pero no hay una migración de datos que fusione cuentas
  de `User` (solo Athletes).
