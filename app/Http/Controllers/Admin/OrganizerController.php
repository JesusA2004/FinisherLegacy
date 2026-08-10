<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrganizerStatus;
use App\Http\Controllers\Controller;
use App\Models\Organizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrganizerController extends Controller
{
    public function index(Request $request): Response
    {
        $organizers = Organizer::query()
            ->withCount('events')
            ->when($request->string('q')->toString(), fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $organizers->through(fn (Organizer $organizer) => [
            'id' => $organizer->id,
            'name' => $organizer->name,
            'email' => $organizer->email ?? '—',
            'website' => $organizer->website ?? '—',
            'status' => $organizer->status->value,
            'events_count' => $organizer->events_count,
        ]);

        return Inertia::render('admin/organizers/Index', [
            'organizers' => $organizers,
            'filters' => ['q' => $request->string('q')->toString()],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(5));

        Organizer::create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Organizador creado.']);

        return back();
    }

    public function update(Request $request, Organizer $organizer): RedirectResponse
    {
        $organizer->update($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Organizador actualizado.']);

        return back();
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'website' => ['nullable', 'url', 'max:255'],
            'status' => ['required', Rule::enum(OrganizerStatus::class)],
        ]);
    }
}
