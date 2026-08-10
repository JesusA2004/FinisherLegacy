<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LegacyCode;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LegacyCodeController extends Controller
{
    public function index(Request $request): Response
    {
        $legacyCodes = LegacyCode::query()
            ->with(['plate', 'user'])
            ->when($request->string('q')->toString(), fn ($q, $search) => $q->where('code', 'like', "%{$search}%"))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $legacyCodes->through(fn (LegacyCode $legacyCode) => [
            'id' => $legacyCode->id,
            'code' => $legacyCode->code,
            'status' => $legacyCode->status->value,
            'owner' => $legacyCode->user !== null ? $legacyCode->user->name : '—',
            'plate' => $legacyCode->plate !== null ? $legacyCode->plate->serial_number : '—',
            'claimed_at' => $legacyCode->claimed_at?->toDateString() ?? '—',
        ]);

        return Inertia::render('admin/legacy-codes/Index', [
            'legacyCodes' => $legacyCodes,
            'filters' => ['q' => $request->string('q')->toString()],
        ]);
    }
}
