<?php

namespace App\Http\Controllers\Admin;

use App\Enums\IncidentStatus;
use App\Enums\PlateStatus;
use App\Http\Controllers\Controller;
use App\Models\AthleteProfile;
use App\Models\Event;
use App\Models\EventIncident;
use App\Models\EventParticipant;
use App\Models\EventPreregistration;
use App\Models\Plate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $today = now()->toDateString();

        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'athletes' => AthleteProfile::count(),
                'events' => Event::count(),
                'participants' => EventParticipant::count(),
                'preregistrations' => EventPreregistration::count(),
                'plates_today' => Plate::whereDate('created_at', $today)->count(),
                'pending_production' => Plate::whereIn('status', [
                    PlateStatus::Draft, PlateStatus::Queued, PlateStatus::Processing,
                ])->count(),
                'delivered' => Plate::where('status', PlateStatus::Delivered)->count(),
                'open_incidents' => EventIncident::where('status', IncidentStatus::Open)->count(),
            ],
            'productionByStatus' => Plate::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ]);
    }
}
