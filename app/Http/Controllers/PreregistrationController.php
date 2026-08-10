<?php

namespace App\Http\Controllers;

use App\Models\EventPreregistration;
use App\Services\PreregistrationService;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class PreregistrationController extends Controller
{
    public function __construct(private readonly PreregistrationService $preregistrations) {}

    public function show(string $token): Response
    {
        $preregistration = EventPreregistration::query()
            ->where('token', $token)
            ->with(['eventEdition.event', 'eventRace'])
            ->firstOrFail();

        return Inertia::render('preregistrations/Show', [
            'token' => $preregistration->token,
            'status' => $preregistration->status->value,
            'firstName' => $preregistration->first_name,
            'lastName' => $preregistration->last_name,
            'bibNumber' => $preregistration->bib_number,
            'event' => [
                'name' => $preregistration->eventEdition->event->name,
                'slug' => $preregistration->eventEdition->event->slug,
            ],
            'edition' => [
                'name' => $preregistration->eventEdition->name,
                'event_date' => $preregistration->eventEdition->event_date->toDateString(),
            ],
            'race' => $preregistration->eventRace->name,
            'qrUrl' => route('preregistrations.qr', $preregistration->token),
        ]);
    }

    public function qr(string $token): HttpResponse
    {
        $preregistration = EventPreregistration::query()->where('token', $token)->firstOrFail();

        return response($this->preregistrations->qrSvg($preregistration), 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
