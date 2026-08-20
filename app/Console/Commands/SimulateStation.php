<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * A Mock Laser Driver stand-in for exercising the Device API contract over
 * real HTTP, with no hardware and no separate desktop app — see
 * docs/adr/0002 §Mock Driver. Deliberately stops after claiming a job: it
 * does NOT advance any front/back/qr checklist state, since that endpoint
 * doesn't exist yet (Slice 2). This is a CLI test client, not part of the
 * domain — it never ships anything the backend "runs" as a driver.
 */
class SimulateStation extends Command
{
    protected $signature = 'finisher:simulate-station
        {--base-url= : Base URL of the Finisher Legacy API (default: app.url)}
        {--token= : An existing device token — skips pairing if given}
        {--name=Simulador CLI : Name to request when pairing}
        {--wait=120 : Seconds to wait for a Super Admin to approve pairing}';

    protected $description = 'Simulate a Finisher Event Desktop station against the Device API (mock driver, no hardware).';

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
        $this->info('Dispositivo: '.json_encode($device->json('data'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $heartbeat = $http->post('device/heartbeat', ['app_version' => 'simulator-1.0']);
        $this->info('Heartbeat: '.json_encode($heartbeat->json('data'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $next = $http->get('production/jobs/next');
        $job = $next->json('data');

        if ($job === null) {
            $this->comment('No hay trabajos en cola.');

            return self::SUCCESS;
        }

        $this->info('Siguiente job disponible: #'.$job['job_id'].' — placa '.$job['serial']);

        $claim = $http->post("production/jobs/{$job['job_id']}/claim");

        if ($claim->failed()) {
            $this->error('No se pudo reclamar el job: '.$claim->body());

            return self::FAILURE;
        }

        $this->info('Job reclamado. Payload del láser (mock — Slice 2 avanza el checklist):');
        $this->line(json_encode($claim->json('data'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}');

        return self::SUCCESS;
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
