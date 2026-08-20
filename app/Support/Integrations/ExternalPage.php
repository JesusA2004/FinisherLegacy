<?php

namespace App\Support\Integrations;

/**
 * One page of participants/results from an adapter — never "the whole
 * roster in one array" (docs/adr/0005 §86-88: a 50,000-participant event
 * must never force `collect()->all()` of the full provider response).
 * SyncExternalParticipants/SyncExternalResults loop calling the adapter
 * again with `nextCursor` until `hasMore` is false.
 *
 * @template T of ExternalParticipantData|ExternalResultData
 */
final class ExternalPage
{
    /**
     * @param  list<T>  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly ?string $nextCursor,
        public readonly bool $hasMore,
    ) {}
}
