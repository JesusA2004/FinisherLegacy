<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * A Mock Laser Driver stand-in for exercising the full Slice 2 physical
 * workflow contract over real HTTP, with no hardware and no separate
 * desktop app — see docs/adr/0002 §Mock Driver and docs/adr/0003 §Mock
 * Driver / Simulator. This is a CLI test client, not part of the domain —
 * it never ships anything the backend "runs" as a driver; every state
 * change still goes through the exact same Device API a real Finisher
 * Event Desktop would call.
 */
class SimulateStation extends Command
{
    protected $signature = 'finisher:simulate-station
        {--base-url= : Base URL of the Finisher Legacy API (default: app.url)}
        {--token= : An existing device token — skips pairing if given}
        {--name=Simulador CLI : Name to request when pairing}
        {--wait=120 : Seconds to wait for a Super Admin to approve pairing}
        {--auto-flip : Confirm the flip automatically instead of prompting for Enter}
        {--non-interactive : Never prompt — implies --auto-flip, for CI/dev}
        {--wrong-qr : Send a deliberately incorrect QR value, to exercise the failure path}';

    protected $description = 'Simulate a Finisher Event Desktop station against the Device API — full Slice 2 workflow, mock driver, no hardware.';

    private const ENGRAVE_SIMULATED_SECONDS = 1;

    public function handle(): int
    {
        $baseUrl = rtrim($this->option('base-url') ?: config('app.url'), '/');
        $token = $this->option('token');

        if (! $token) {
            $token = $this->pair($baseUrl);

            if ($token === null) {
                return self::FAILURE;
            }
        }

        $http = Http::baseUrl($baseUrl.'/api/v1')->withToken($token)->acceptJson();

        $device = $http->get('device');
        if ($device->failed()) {
            $this->error('No se pudo leer el dispositivo — ¿token inválido o revocado? '.$device->body());

            return self::FAILURE;
        }

        $heartbeat = $http->post('device/heartbeat', ['app_version' => 'simulator-1.0']);
        if ($heartbeat->failed()) {
            $this->error('Heartbeat falló: '.$heartbeat->body());

            return self::FAILURE;
        }

        $next = $http->get('production/jobs/next');
        $job = $next->json('data');

        if ($job === null) {
            $this->comment('No hay trabajos en cola.');

            return self::SUCCESS;
        }

        $jobId = $job['job_id'];
        $this->info("Siguiente job disponible: #{$jobId} — placa {$job['plate']['serial']}");

        $job = $this->step($http, "production/jobs/{$jobId}/claim", 'Job reclamado.');
        if ($job === null) {
            return self::FAILURE;
        }

        if (! $this->downloadAndVerify($http, $job, 'front') || ! $this->downloadAndVerify($http, $job, 'back')) {
            return self::FAILURE;
        }

        $job = $this->step($http, "production/jobs/{$jobId}/prepare", 'Preparación iniciada.');
        if ($job === null) {
            return self::FAILURE;
        }

        $job = $this->step($http, "production/jobs/{$jobId}/front/start", 'Grabando frente…');
        if ($job === null) {
            return self::FAILURE;
        }
        sleep(self::ENGRAVE_SIMULATED_SECONDS);

        $job = $this->step($http, "production/jobs/{$jobId}/front/complete", 'Frente grabado.');
        if ($job === null) {
            return self::FAILURE;
        }

        $this->newLine();
        $this->warn('  >>> VOLTEA LA PLACA <<<');
        $this->confirmFlip();

        $job = $this->step($http, "production/jobs/{$jobId}/flip/confirm", 'Volteo confirmado.');
        if ($job === null) {
            return self::FAILURE;
        }

        $job = $this->step($http, "production/jobs/{$jobId}/back/start", 'Grabando reverso…');
        if ($job === null) {
            return self::FAILURE;
        }
        sleep(self::ENGRAVE_SIMULATED_SECONDS);

        $job = $this->step($http, "production/jobs/{$jobId}/back/complete", 'Reverso grabado.');
        if ($job === null) {
            return self::FAILURE;
        }

        $legacyCode = $job['plate']['legacy_code'] ?? null;
        $scanned = $this->option('wrong-qr')
            ? route('legacy-code.show', 'FL-WRONGCODE')
            : ($legacyCode !== null ? route('legacy-code.show', $legacyCode) : 'FL-UNKNOWN');

        $this->comment("Simulando escáner QR: {$scanned}");
        $qrResponse = $http->post("production/jobs/{$jobId}/qr/verify", ['decoded_value' => $scanned]);

        if ($qrResponse->failed()) {
            $this->error('QR incorrecto — el job se queda en verifying_qr: '.$qrResponse->json('error.message', $qrResponse->body()));

            return $this->option('wrong-qr') ? self::SUCCESS : self::FAILURE;
        }

        $job = $qrResponse->json('data');
        $this->info('QR verificado — placa lista.');

        $deliverResponse = $http->post("production/jobs/{$jobId}/deliver");
        if ($deliverResponse->failed()) {
            $this->error('No se pudo marcar como entregada: '.$deliverResponse->body());

            return self::FAILURE;
        }

        $this->info('Placa entregada. Flujo completo. Estado final:');
        $this->line(json_encode($deliverResponse->json('data'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}');

        return self::SUCCESS;
    }

    /**
     * POSTs a workflow transition and prints its outcome — every step in
     * the flow below is the same shape: call, fail loudly with the
     * server's error message, or report the new status.
     *
     * @return array<string, mixed>|null
     */
    private function step(PendingRequest $http, string $path, string $label): ?array
    {
        $response = $http->post($path);

        if ($response->failed()) {
            $code = $response->json('error.code', 'HTTP_'.$response->status());
            $message = $response->json('error.message', $response->body());
            $this->error("[{$code}] {$message}");

            return null;
        }

        $data = $response->json('data');
        $this->info("{$label} (status: {$data['status']})");

        return $data;
    }

    private function confirmFlip(): void
    {
        if ($this->option('auto-flip') || $this->option('non-interactive')) {
            $this->comment('(--auto-flip) Volteo confirmado automáticamente.');

            return;
        }

        $this->ask('Presiona Enter cuando la placa esté volteada');
    }

    /**
     * @param  array<string, mixed>  $job
     */
    private function downloadAndVerify(PendingRequest $http, array $job, string $face): bool
    {
        $expectedHash = $job[$face]['sha256'] ?? null;
        $downloadUrl = $job[$face]['download_url'] ?? null;

        if ($expectedHash === null || $downloadUrl === null) {
            $this->error("El payload no trae {$face}.sha256/download_url todavía.");

            return false;
        }

        $path = Str::after($downloadUrl, '/api/v1/');
        $response = $http->get($path);

        if ($response->failed()) {
            $this->error("No se pudo descargar el artifact {$face}: ".$response->body());

            return false;
        }

        $actualHash = hash('sha256', $response->body());

        if ($actualHash !== $expectedHash) {
            $this->error("Hash de {$face} NO coincide — esperado {$expectedHash}, obtenido {$actualHash}.");

            return false;
        }

        $this->info("Artifact {$face} descargado y verificado (sha256 coincide).");

        return true;
    }

    private function pair(string $baseUrl): ?string
    {
        $http = Http::baseUrl($baseUrl.'/api/v1')->acceptJson();

        $pair = $http->post('devices/pair', ['name' => $this->option('name')]);

        if ($pair->failed()) {
            $this->error('No se pudo solicitar el emparejamiento: '.$pair->body());

            return null;
        }

        $code = $pair->json('data.code');
        $pollToken = $pair->json('data.poll_token');

        $this->info("Código de emparejamiento: {$code}");
        $this->comment('Apruébalo en /admin/production-devices (Super Admin) — esperando...');

        $deadline = now()->addSeconds((int) $this->option('wait'));

        while (now()->lessThan($deadline)) {
            $confirm = $http->post('devices/pair/confirm', ['poll_token' => $pollToken]);

            if ($confirm->failed()) {
                $this->error('El emparejamiento fue rechazado o expiró: '.$confirm->body());

                return null;
            }

            if ($confirm->json('data.status') === 'completed') {
                $this->info('Emparejado. Token recibido (no se vuelve a mostrar).');

                return $confirm->json('data.token');
            }

            sleep(3);
        }

        $this->error('Se agotó el tiempo de espera por aprobación.');

        return null;
    }
}
