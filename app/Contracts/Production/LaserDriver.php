<?php

namespace App\Contracts\Production;

use App\Support\Production\DriverCapabilities;
use App\Support\Production\DriverConnectionResult;
use App\Support\Production\DriverStatus;
use App\Support\Production\EngraveJob;
use App\Support\Production\EngraveResult;

/**
 * What a Desktop Event Station's laser integration must look like —
 * defined here as a reference contract for `App\Services\Production\Drivers\MockLaserDriver`
 * and the real drivers a future Desktop app will implement in whatever
 * technology ADR 0007 lands on. Never called from a web request — this is
 * local-machine hardware access, out of Laravel's process by construction
 * once a real Desktop exists. See docs/adr/0007-desktop-event-station.md.
 *
 * Capability-based: `frame()`/`pause()`/`resume()` are only meaningful
 * when `capabilities()` says so — a caller checks before calling.
 */
interface LaserDriver
{
    public function connect(): DriverConnectionResult;

    public function disconnect(): void;

    public function getStatus(): DriverStatus;

    public function isReady(): bool;

    public function frame(EngraveJob $job): void;

    public function engrave(EngraveJob $job): EngraveResult;

    public function pause(): void;

    public function resume(): void;

    public function cancel(): void;

    public function capabilities(): DriverCapabilities;
}
