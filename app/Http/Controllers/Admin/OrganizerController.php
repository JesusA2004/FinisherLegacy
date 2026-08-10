<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrganizerStatus;
use App\Http\Controllers\Controller;
use App\Models\Organizer;
use App\Services\ImageProcessingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrganizerController extends Controller
{
    public function __construct(private readonly ImageProcessingService $images) {}

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
            'legal_name' => $organizer->legal_name,
            'email' => $organizer->email ?? '—',
            'phone' => $organizer->phone,
            'website' => $organizer->website ?? '—',
            'status' => $organizer->status->value,
            'events_count' => $organizer->events_count,
            'logo_url' => $organizer->logo_path ? Storage::disk('public')->url($organizer->logo_path) : null,
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

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $this->images->process($request->file('logo'), 'organizers', withThumbnail: false, cropSquare: 512)['display_path'];
        }

        Organizer::create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Organizador creado.']);

        return back();
    }

    public function update(Request $request, Organizer $organizer): RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('logo')) {
            if ($organizer->logo_path) {
                $this->images->delete([$organizer->logo_path]);
            }

            $data['logo_path'] = $this->images->process($request->file('logo'), 'organizers', withThumbnail: false, cropSquare: 512)['display_path'];
        }

        $organizer->update($data);

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
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:4096'],
        ]);
    }
}
