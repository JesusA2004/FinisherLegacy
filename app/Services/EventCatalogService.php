<?php

namespace App\Services;

use App\Enums\EditionStatus;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventEdition;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Shared "browse published events" query logic — the web /events index and
 * the /api/v1/events endpoint both filter/paginate through this so the
 * "what counts as a published, browsable edition" rule only lives once.
 */
class EventCatalogService
{
    /**
     * @param  array{q?: ?string, sport?: ?string, status?: ?string}  $filters
     * @return LengthAwarePaginator<int, EventEdition>
     */
    public function publishedEditions(array $filters, int $perPage = 9): LengthAwarePaginator
    {
        $search = trim((string) ($filters['q'] ?? ''));
        $sportSlug = $filters['sport'] ?? null;
        $phase = $filters['status'] ?? null;

        $query = EventEdition::query()
            ->whereHas('event', fn ($q) => $q->where('status', EventStatus::Published))
            ->where('status', EditionStatus::Published)
            ->with(['event.sport', 'races']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('city', 'like', "%{$search}%")
                    ->orWhereHas('event', fn ($eq) => $eq->where('name', 'like', "%{$search}%"));
            });
        }

        if (is_string($sportSlug) && $sportSlug !== '') {
            $query->whereHas('event.sport', fn ($q) => $q->where('slug', $sportSlug));
        }

        $today = now()->toDateString();

        if (in_array($phase, ['upcoming', 'ongoing', 'finished'], true)) {
            match ($phase) {
                'upcoming' => $query->whereDate('event_date', '>', $today),
                'ongoing' => $query->whereDate('event_date', '=', $today),
                'finished' => $query->whereDate('event_date', '<', $today),
            };
        } else {
            $query->whereDate('event_date', '>=', $today);
        }

        return $query->orderBy('event_date')->paginate($perPage)->withQueryString();
    }

    public function currentEdition(Event $event): ?EventEdition
    {
        /** @var Collection<int, EventEdition> $editions */
        $editions = $event->editions()
            ->where('status', EditionStatus::Published)
            ->with(['races' => fn ($q) => $q->where('active', true)->orderBy('distance_value')])
            ->orderBy('event_date')
            ->get();

        return $editions->first(fn (EventEdition $edition) => ! $edition->event_date->isPast()) ?? $editions->last();
    }
}
