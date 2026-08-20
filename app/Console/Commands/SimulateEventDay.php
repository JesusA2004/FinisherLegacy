<?php

namespace App\Console\Commands;

use App\Actions\Integrations\CreateEventFromExternalData;
use App\Actions\Integrations\LinkExternalEvent;
use App\Actions\Integrations\SyncExternalEvent;
use App\Actions\Integrations\TestProviderConnection;
use App\Actions\Production\CompleteBackEngraving;
use App\Actions\Production\CompleteFrontEngraving;
use App\Actions\Production\ConfirmPlateFlip;
use App\Actions\Production\DeliverProductionPlate;
use App\Actions\Production\StartBackEngraving;
use App\Actions\Production\StartFrontEngraving;
use App\Actions\Production\StartProductionPreparation;
use App\Actions\Production\VerifyProductionQr;
use App\Enums\ExternalSyncType;
use App\Enums\PlateTemplateVersionStatus;
use App\Enums\ProductionDeviceStatus;
use App\Enums\ProviderConnectionStatus;
use App\Models\EventParticipant;
use App\Models\EventPlateTemplate;
use App\Models\ExternalEventMapping;
use App\Models\PlateTemplateVersion;
use App\Models\ProductionDevice;
use App\Models\ProviderConnection;
use App\Models\Sport;
use App\Services\Devices\ProductionJobClaimService;
use App\Services\Integrations\EventProviderRegistry;
use App\Services\PlateEligibilityService;
use App\Services\PlateGenerationService;
use App\Services\Production\Drivers\MockLaserDriver;
use App\Support\Production\EngraveJob;
use App\Support\Production\VerifyQrData;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * The full Slice 5 criterion in one command: Mock Event Provider → Event
 * Ops search → produce → station claims → MockLaserDriver → delivered —
 * with no HTTP server required, since every step calls the exact same
 * Action classes the web/Device API routes call (docs/adr/0006 §6,
 * docs/adr/0007 §4). `--bib` defaults to 1050 (inside the Mock provider's
 * 1001-1100 range) rather than the illustrative "1425" from the product
 * brief, which the mock roster doesn't generate.
 */
class SimulateEventDay extends Command
{
    protected $signature = 'finisher:simulate-event-day
        {--bib=1050 : Bib number to search and produce}
        {--finishers=60 : How many mock finishers to sync before searching}';

    protected $description = 'End-to-end demo: Mock Event Provider sync → Event Ops search+produce → station engraves via MockLaserDriver → delivered.';

    public function handle(
        TestProviderConnection $testConnection,
        CreateEventFromExternalData $createEvent,
        LinkExternalEvent $linkEvent,
        SyncExternalEvent $syncEvent,
        EventProviderRegistry $registry,
        PlateEligibilityService $eligibility,
        PlateGenerationService $plates,
        ProductionJobClaimService $claimService,
        StartProductionPreparation $prepare,
        StartFrontEngraving $startFront,
        CompleteFrontEngraving $completeFront,
        ConfirmPlateFlip $confirmFlip,
        StartBackEngraving $startBack,
        CompleteBackEngraving $completeBack,
        VerifyProductionQr $verifyQr,
        DeliverProductionPlate $deliver,
    ): int {
        $bib = (string) $this->option('bib');
        $finisherCount = (int) $this->option('finishers');

        // 1-2: Provider sync (docs/adr/0005).
        $connection = ProviderConnection::query()->firstOrCreate(
            ['provider_key' => 'mock', 'name' => 'Mock Event Provider (simulate-event-day)'],
            ['uuid' => (string) Str::uuid(), 'status' => ProviderConnectionStatus::Untested, 'settings' => ['chunk_size' => 40]],
        );
        $testConnection->handle($connection);

        $adapter = $registry->get('mock');
        $eventData = $adapter->listEvents($connection)[0];

        $mapping = ExternalEventMapping::query()
            ->where('provider_connection_id', $connection->id)
            ->where('external_event_id', $eventData->externalId)
            ->first();

        if ($mapping === null) {
            $sport = Sport::query()->first() ?? Sport::create(['name' => 'Running', 'slug' => 'running', 'active' => true, 'sort_order' => 1]);
            $mapping = $createEvent->handle($eventData, $connection, $sport);
        } else {
            $mapping = $linkEvent->handle($connection, $eventData->externalId, $mapping->eventEdition);
        }
        $edition = $mapping->eventEdition;

        $rosterRun = $syncEvent->handle($mapping, ExternalSyncType::Roster);
        $this->log("Provider sync OK — {$rosterRun->participants_received} participantes");

        $connection->update(['settings' => array_merge($connection->settingsArray(), ['mock_finishers_count' => $finisherCount])]);
        $syncEvent->handle($mapping->fresh(), ExternalSyncType::Results);

        // Ensure a plate template is assigned — a fresh Mock event has none.
        if ($edition->defaultPlateTemplateVersion() === null) {
            $version = PlateTemplateVersion::factory()->create(['status' => PlateTemplateVersionStatus::Published]);
            EventPlateTemplate::create(['event_edition_id' => $edition->id, 'plate_template_version_id' => $version->id, 'is_default' => true, 'active' => true]);
        }

        // 3: Event Ops search.
        $participant = EventParticipant::where('event_edition_id', $edition->id)->where('bib_number', $bib)->first();
        if ($participant === null) {
            $this->error("No se encontró el dorsal {$bib} — sube --finishers o cambia --bib.");

            return self::FAILURE;
        }

        $check = $eligibility->check($participant);
        $this->log("Resultado bib {$bib} disponible — {$participant->full_name} ({$participant->result?->official_time})");

        if (! $check->eligible) {
            $this->error('No elegible para producir: '.implode(', ', $check->reasons));

            return self::FAILURE;
        }

        // 4-5: Produce (same PlateGenerationService Event Ops/OperatorController uses).
        $plate = $plates->generateIntegrated($participant);
        $job = $plate->latestProductionJob;
        $this->log("Placa {$plate->serial_number} creada — Job #{$job->id} en cola");

        // 6-7: Station.
        $device = ProductionDevice::query()->firstOrCreate(
            ['name' => 'EVENT-01', 'event_edition_id' => $edition->id],
            ['uuid' => (string) Str::uuid(), 'station_code' => 'EVENT-01', 'status' => ProductionDeviceStatus::Active, 'last_seen_at' => now()],
        );
        $device->update(['last_seen_at' => now()]);

        $job = $claimService->claim($job, $device);
        $this->log("Job #{$job->id} reclamado por {$device->name}");

        $driver = new MockLaserDriver(connectDelayMs: 20, frontDurationMs: 30, backDurationMs: 30);
        $connect = $driver->connect();
        if (! $connect->connected) {
            $this->error("MockLaserDriver no conectó: {$connect->error}");

            return self::FAILURE;
        }

        $job = $prepare->handle($job, $device);
        $job = $startFront->handle($job, $device);
        $this->log('Grabando frente…');
        $frontResult = $driver->engrave(new EngraveJob($job->id, 'front', "jobs/{$job->id}/front.svg"));
        if (! $frontResult->success) {
            $this->error("Fallo simulado en frente: {$frontResult->errorMessage}");

            return self::FAILURE;
        }
        $job = $completeFront->handle($job, $device);
        $this->log('Frente terminado — VOLTEA LA PLACA');

        $job = $confirmFlip->handle($job, $device);
        $job = $startBack->handle($job, $device);
        $this->log('Grabando reverso…');
        $backResult = $driver->engrave(new EngraveJob($job->id, 'back', "jobs/{$job->id}/back.svg"));
        if (! $backResult->success) {
            $this->error("Fallo simulado en reverso: {$backResult->errorMessage}");

            return self::FAILURE;
        }
        $job = $completeBack->handle($job, $device);

        $job->loadMissing('plate.legacyCode');
        $qrValue = route('legacy-code.show', $job->plate->legacyCode->code);
        $job = $verifyQr->handle($job, $device, VerifyQrData::make($qrValue));
        $this->log('QR correcto — LISTA');

        $job = $deliver->handle($job, $device);
        $this->log("ENTREGADA — placa {$job->plate->serial_number} / Legacy Code {$job->plate->legacyCode->code}");

        $driver->disconnect();
        $this->info('Simulación de día de evento completa.');

        return self::SUCCESS;
    }

    private function log(string $message): void
    {
        $this->info('['.now()->format('H:i:s').'] '.$message);
    }
}
