# Arquitectura — índice

Mapa rápido de los documentos de arquitectura. Para decisiones y su
razonamiento, ver `docs/adr/`; estos documentos son el "cómo se organiza
el código", no el "por qué se decidió así".

| Documento | Cubre |
|---|---|
| `docs/architecture/athlete-identity.md` | Athlete canónico, deduplicación, `AthleteIdentityMatcher` (Slice 3) |
| `docs/architecture/external-event-providers.md` | Provider adapters, DTOs canónicos, Mock provider (Slice 4) |
| `docs/architecture/ingestion-pipeline.md` | CSV/API → `EventIngestionService` → Actions de Slice 3/4 |
| `docs/plate-production.md` | Plate Studio, generación de placas, Event Ops (Slice 5) |
| `docs/legacy-code-lifecycle.md` | Legacy Code, claim, permanencia |
| `docs/device-api/v1.md` | Contrato HTTP que un Desktop/simulador consume |
| `docs/event-ops/README.md` | Dashboard operativo, búsqueda, elegibilidad (Slice 5) |
| `docs/desktop/technology-decision.md` | Comparación .NET/Tauri/Electron para el Desktop real |

## Capas, de arriba hacia abajo

```
Web Controller (Inertia)     API Controller (/api/v1)     Device Controller (/api/v1, auth:sanctum device)
        └───────────────────────────┬───────────────────────────┘
                                     ▼
                      Actions (App\Actions\*)  — una por caso de uso
                                     ▼
                       Services/Queries (App\Services\*, App\Queries\*)
                                     ▼
                                  Models
```

Ningún controller reimplementa una regla que ya vive en un Action —
Web, API pública y Device API llaman exactamente las mismas Actions
cuando expresan el mismo caso de uso (ver docs/adr/0003 §8 para el
ejemplo más explícito: producción física).
