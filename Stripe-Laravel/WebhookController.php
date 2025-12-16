<?php
declare(strict_types=1);

namespace Stripe\Controller;

use App\Http\Controllers\Controller;
use App\Models\PaymentChannel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Actions\HandleCheckoutSessionCompletedAction;
use Stripe\Enums\StripeEventType;
use Stripe\Services\StripeService;
use Illuminate\Support\Facades\Log;
use Stripe\Support\InMemoryEventStore;
use Exception;

class WebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        Log::info('Webhook Stripe primit.');
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = $this->getWebhookSecret();

        if (!$endpointSecret || !$sigHeader) {
            return response('', 400);
        }

        try {
            $service = new StripeService(new \Stripe\StripeClient('dummy'));
            $event = $service->constructEvent($payload, $sigHeader, $endpointSecret);
            
        } catch (Exception $e) {
            return response('', 400);
        }

        $this->processEvent($event);

        return response('', 200);
    }

    public function processEvent($event): void
    {
        $type = $event->type ?? ($event['type'] ?? null);
        $dataObject = $event->data->object ?? ($event['data']['object'] ?? null);

        $orderId = $dataObject->client_reference_id
            ?? (isset($dataObject->metadata) && isset($dataObject->metadata->order_id) ? $dataObject->metadata->order_id : null);
        $eventId = $event->id ?? ($event['id'] ?? null);
        if ($eventId && $type) {
            InMemoryEventStore::record($eventId, $type, $orderId);
        }

        if ($type === StripeEventType::CHECKOUT_SESSION_COMPLETED->value) {
            $action = app(HandleCheckoutSessionCompletedAction::class);
            $action->execute($dataObject);
        }
    }

    private function getWebhookSecret(): ?string
    {
        $paymentChannel = PaymentChannel::where('class_name', PaymentChannel::$stripe)
            ->where('status', 'active')
            ->first();

        if ($paymentChannel && is_array($paymentChannel->credentials)) {
            return $paymentChannel->credentials['webhook_secret'] ?? env('STRIPE_WEBHOOK_SECRET');
        }

        return env('STRIPE_WEBHOOK_SECRET');
    }
}
