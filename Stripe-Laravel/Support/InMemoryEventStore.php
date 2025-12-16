<?php
declare(strict_types=1);

namespace Stripe\Support;

class InMemoryEventStore
{
    private static array $events = [];

    public static function record(string $eventId, string $eventType, ?string $orderId, ?string $sessionId = null): void
    {
        self::$events[] = [
            'event_id' => $eventId,
            'event_type' => $eventType,
            'order_id' => $orderId,
            'session_id' => $sessionId,
            'created_at' => now(),
        ];
    }

    public static function all(): array
    {
        return self::$events;
    }
}
