<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MachineProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Machine Profile is a workflow label an operator recognizes ("Fiber 30W —
 * LightBurn"), never a driver — Finisher Legacy does not talk to the laser.
 * Deliberately no power/speed/frequency fields: those are calibrated
 * physically per machine/material, never shipped as a default. See
 * docs/plate-production.md §9.
 */
class MachineProfileController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/machine-profiles/Index', [
            'profiles' => MachineProfile::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        MachineProfile::create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Perfil de máquina creado.']);

        return back();
    }

    public function update(Request $request, MachineProfile $machineProfile): RedirectResponse
    {
        $data = $this->validated($request);
        $machineProfile->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Perfil de máquina actualizado.']);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'max:50'],
            'software' => ['nullable', 'string', 'max:100'],
            'default_format' => ['required', Rule::in(['svg', 'png', 'pdf'])],
            'width_mm' => ['nullable', 'numeric', 'min:1', 'max:2000'],
            'height_mm' => ['nullable', 'numeric', 'min:1', 'max:2000'],
            'active' => ['required', 'boolean'],
        ]);
    }
}
