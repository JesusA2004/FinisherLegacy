<?php

namespace App\Services\Production\Drivers;

use App\Contracts\Production\LaserDriver;
use App\Support\Production\DriverCapabilities;
use App\Support\Production\DriverConnectionResult;
use App\Support\Production\DriverStatus;
use App\Support\Production\EngraveJob;
use App\Support\Production\EngraveResult;

/**
 * Simulates a fiber laser + its controller software for
 * `finisher:simulate-event-day` — no hardware, no real SDK (docs/adr/0007
 * §Mock driver). Configurable failure/latency is dev-only, injected by the
 * caller (the simulator command) — a real Desktop never lets the backend
 * dictate these (§115-116, safety boundary).
 */
class MockLaserDriver implements LaserDriver
{
    private bool $connected = false;

    /**
     * @param  'interlock_open'|'device_not_ready'|'engraving_failed'|null  $simulateFailure
     */
    public function __construct(
        private readonly int $connectDelayMs = 50,
        private readonly int $frontDurationMs = 50,
        private readonly int $backDurationMs = 50,
        private readonly ?string $simulateFailure = null,
    ) {}

    public function connect(): DriverConnectionResult
    {
        usleep($this->connectDelayMs * 1000);

        if ($this->simulateFailure === 'device_not_ready') {
            return new DriverConnectionResult(false, 'El dispositivo no respondió.');
        }

        $this->connected = true;

        return new DriverConnectionResult(true);
    }

    public function disconnect(): void
    {
        $this->connected = false;
    }

    public function getStatus(): DriverStatus
    {
        if (! $this->connected) {
            return DriverStatus::notReady('No conectado.');
        }

        if ($this->simulateFailure === 'interlock_open') {
            return DriverStatus::interlockOpen();
        }

        return DriverStatus::ready();
    }

    public function isReady(): bool
    {
        return $this->getStatus()->code === 'ready';
    }

    public function frame(EngraveJob $job): void
    {
        // Mock has no real optics to trace a frame with — a no-op that
        // exists purely to satisfy callers checking capabilities() first.
    }

    public function engrave(EngraveJob $job): EngraveResult
    {
        if (! $this->isReady()) {
            return EngraveResult::failed('DEVICE_NOT_READY', $this->getStatus()->detail ?? 'El driver no está listo.');
        }

        $durationMs = $job->face === 'front' ? $this->frontDurationMs : $this->backDurationMs;
        usleep($durationMs * 1000);

        if ($this->simulateFailure === 'engraving_failed') {
            return EngraveResult::failed('ENGRAVING_FAILED', 'Falla simulada durante el grabado.');
        }

        return EngraveResult::ok();
    }

    public function pause(): void
    {
        // Not supported — see capabilities().
    }

    public function resume(): void
    {
        // Not supported — see capabilities().
    }

    public function cancel(): void
    {
        $this->connected = false;
    }

    public function capabilities(): DriverCapabilities
    {
        return new DriverCapabilities(supportsFraming: false, supportsPauseResume: false);
    }
}
