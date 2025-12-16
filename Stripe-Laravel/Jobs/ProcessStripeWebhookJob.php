<?php
declare(strict_types=1);

namespace Stripe\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Stripe\Actions\HandleCheckoutSessionCompletedAction;
use Stripe\Enums\StripeEventType;
use Illuminate\Support\Facades\Log;
use Stripe\Support\InMemoryEventStore;
use Stripe\Services\IdempotencyService;

class ProcessStripeWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var object
     */
    public $eventData;
    public string $eventType;
    public string $eventId;

    public function __construct(string $eventId, string $eventType, object $dataObject)
    {
        $this->eventId = $eventId;
        $this->eventType = $eventType;
        $this->eventData = $dataObject;
    }

    public function handle(): void
    {
        Log::info("Job pornit pentru eveniment: {$this->eventType}", ['event_id' => $this->eventId]);

        $orderId = $this->eventData->client_reference_id
            ?? (isset($this->eventData->metadata) && isset($this->eventData->metadata->order_id) ? $this->eventData->metadata->order_id : null);
        $idempotency = app(IdempotencyService::class);
        $idempotency->atomicLock($this->eventId, function () use ($orderId) {
            InMemoryEventStore::record($this->eventId, $this->eventType, $orderId);

            if ($this->eventType === StripeEventType::CHECKOUT_SESSION_COMPLETED->value) {
                $action = app(HandleCheckoutSessionCompletedAction::class);
                $action->execute($this->eventData);
            }
        });
        
    }
}
