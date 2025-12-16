<?php
declare(strict_types=1);

namespace Stripe\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IdempotencyService
{
    /**
     * Verifica daca un eveniment a fost deja procesat.     
     */
    public function isEventProcessed(string $eventId): bool
    {
        // Folosind Cache/Redis 
        // Cheia expira dupa 24h, suficient pentru retry-urile Stripe
        return Cache::has("stripe_event:{$eventId}");
    }

    /**
     * Marcheaza evenimentul ca procesat pentru a preveni executarea dubla (Race Conditions).
     */
    public function markEventAsProcessed(string $eventId): void
    {
        Cache::put("stripe_event:{$eventId}", true, now()->addDay());
        Log::info("Idempotency: Event marked as processed.", ['event_id' => $eventId]);
    }

    /**
     * Foloseste un mecanism de Atomic Lock pentru a preveni Race Conditions
     * in cazul in care doua cereri vin exact in aceeasi milisecunda.
     */
    public function atomicLock(string $eventId, callable $callback): void
    {
        // "atomic_lock" previne ca doua procese sa intre aici simultan
        Cache::lock("lock_event:{$eventId}", 10)->get(function () use ($eventId, $callback) {
            if ($this->isEventProcessed($eventId)) {
                Log::info("Idempotency: Event blocked by atomic lock (already processed).", ['event_id' => $eventId]);
                return;
            }

            $callback();

            $this->markEventAsProcessed($eventId);
        });
    }
}
