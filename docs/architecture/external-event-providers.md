# Proveedores de eventos externos

Ver decisión completa en `docs/adr/0005-unified-event-ingestion.md`. Este
documento es el mapa rápido de "qué archivo hace qué".

## Árbol

```
app/Contracts/Integrations/
    EventProviderAdapter.php       Contrato que implementa cada fuente
    VerifiesWebhooks.php           Capability opcional (sin endpoint público todavía)

app/Support/Integrations/
    ExternalEventData.php          Evento
    ExternalRaceData.php           Carrera dentro de un evento
    ExternalParticipantData.php    Fila de roster
    ExternalResultData.php         Resultado de un participante
    ExternalSplitData.php          Un split dentro de un resultado
    ExternalPage.php               Página paginada (items + nextCursor + hasMore)
    ProviderConnectionTestResult.php

app/Services/Integrations/
    EventProviderRegistry.php      provider_key → clase adapter
    EventIngestionService.php      Orquestador: DTO → Actions de Slice 3/4
    Providers/
        MockEventProviderAdapter.php

app/Actions/Integrations/
    TestProviderConnection.php
    LinkExternalEvent.php
    CreateEventFromExternalData.php
    SyncExternalEvent.php          Orquesta un sync run completo
    SyncExternalParticipants.php
    SyncExternalResults.php
    IngestEventResult.php          Equivalente de IngestEventParticipant para resultados

app/Jobs/SyncExternalEventJob.php  Dispatchado por "Sincronizar ahora"

app/Models/
    ProviderConnection.php
    ExternalEventMapping.php
    ExternalRaceMapping.php
    ExternalParticipantMapping.php
    ExternalSyncRun.php
    ExternalSyncError.php
```

## Añadir un proveedor real

1. Crear `App\Services\Integrations\Providers\{Nombre}EventProviderAdapter`
   implementando `EventProviderAdapter`.
2. Registrarlo en `EventProviderRegistry::ADAPTERS`.
3. Si hace HTTP real: usar `Illuminate\Support\Facades\Http`, timeout
   configurable, manejar 429 (`ProviderRateLimitedException` con
   `Retry-After`) y errores de conexión
   (`ProviderUnavailableException`) — nunca dejar una excepción HTTP cruda
   escapar de `fetchParticipants`/`fetchResults`.
4. Si el proveedor tiene webhooks: implementar `VerifiesWebhooks` y
   `supportsWebhooks(): true`. La ruta pública sigue sin existir — crearla
   junto con la verificación de firma, nunca antes.

Nada más del pipeline cambia — `EventIngestionService`, las Actions de
sync y la UI de `/admin/integrations` funcionan igual para cualquier
`provider_key` registrado.

## Comando de demo

```
php artisan finisher:simulate-live-event
```

Crea/reutiliza una conexión Mock, vincula su único evento, sincroniza el
roster (100 participantes) y luego corre tres syncs de resultados
avanzando el reloj mock (20 → 55 → 100 finishers), imprimiendo contadores
en cada paso.
