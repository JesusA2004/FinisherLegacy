# Pipeline de ingestión

Ver `docs/adr/0005-unified-event-ingestion.md` para la decisión completa.
Resumen operativo:

```
CSV/XLSX row  ──┐
Mock/API sync ──┼──> ExternalParticipantData / ExternalResultData (DTO)
                │
                ▼
    App\Services\Integrations\EventIngestionService
                │
    ingestParticipant()                    ingestResult()
                │                                   │
                ▼                                   ▼
  App\Actions\Athletes\IngestEventParticipant   App\Actions\Integrations\IngestEventResult
                │                                   │
                ▼                                   ▼
  App\Actions\Athletes\ResolveAthleteIdentity    EventResult + EventResultSplit
       (matcher, conflictos — ADR 0004)          (upsert idempotente, respeta
                │                                  manual_override_fields)
                ▼
        EventParticipant.athlete_id
```

## Invariantes

- **Idempotencia**: el mismo participante recibido N veces produce 1
  `EventParticipant` (clave `event_edition_id`+`bib_number`, sin cambios
  desde Slice 3). El mismo resultado recibido N veces produce 1
  `EventResult` (clave única `event_participant_id`) y splits sin duplicar
  (clave `event_result_id`+`sequence`).
- **Snapshot de Plate**: `PlateGenerationService` copia `official_time`/
  `pace`/etc. a la fila `plates` en el momento de generación — una
  actualización posterior de `EventResult` (corrección del proveedor)
  nunca modifica una Plate ya generada.
- **Aislamiento de errores**: una fila mala nunca aborta el resto — se
  registra como `ExternalSyncError` y el sync continúa. Solo una falla de
  conexión aborta el run completo.
- **No hay dos pipelines**: CSV y API sync terminan exactamente en las
  mismas dos Actions. Ningún adapter, importador o controller llama
  `EventParticipant::create()`/`EventResult::create()` directamente.
