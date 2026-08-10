<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class AuditController extends Controller
{
    public function index(Request $request): Response
    {
        $activities = Activity::query()
            ->with('causer')
            ->when($request->integer('user_id'), fn ($q, $userId) => $q->where('causer_id', $userId)->where('causer_type', User::class))
            ->when($request->string('event')->toString(), fn ($q, $event) => $q->where('event', $event))
            ->when($request->string('subject_type')->toString(), fn ($q, $type) => $q->where('subject_type', $type))
            ->when($request->date('from'), fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($request->date('to'), fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $activities->through(fn (Activity $activity) => [
            'id' => $activity->id,
            'causer' => $activity->causer instanceof User ? $activity->causer->name : 'Sistema',
            'event' => $activity->event ?? '—',
            'subject_type' => $activity->subject_type ? class_basename($activity->subject_type) : '—',
            'subject_id' => $activity->subject_id,
            'description' => $activity->description,
            'created_at' => $activity->created_at->format('d/m/Y H:i'),
        ]);

        return Inertia::render('admin/audit/Index', [
            'activities' => $activities,
            'events' => Activity::query()->distinct()->orderBy('event')->pluck('event')->filter()->values(),
            'subjectTypes' => Activity::query()
                ->distinct()
                ->orderBy('subject_type')
                ->pluck('subject_type')
                ->filter()
                ->map(fn (string $type) => ['value' => $type, 'label' => class_basename($type)])
                ->values(),
            'users' => User::query()->orderBy('first_name')->get()->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
            ]),
            'filters' => [
                'user_id' => $request->integer('user_id') ?: null,
                'event' => $request->string('event')->toString(),
                'subject_type' => $request->string('subject_type')->toString(),
                'from' => $request->string('from')->toString(),
                'to' => $request->string('to')->toString(),
            ],
        ]);
    }
}
