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
use Exception;

class WebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        Log::info('Webhook Stripe primit.');

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = $this->getWebhookSecret();

        if (!$endpointSecret) {
            Log::critical('Secret webhook Stripe lipsa din configuratie.');
            return response('Webhook secret lipsa', 500);
        }

        if (!$sigHeader) {
            Log::warning('Webhook Stripe primit fara antet de semnatura.');
            return response('Semnatura lipsa', 400);
        }

        try {
                        
            $service = new StripeService(new \Stripe\StripeClient('dummy'));
            $event = $service->constructEvent($payload, $sigHeader, $endpointSecret);
            
            Log::info('Webhook Stripe verificat cu succes.', ['event_type' => $event->type ?? 'necunoscut']);

        } catch (Exception $e) {
            Log::error('Verificarea semnaturii webhook Stripe a esuat.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response('Semnatura invalida', 400);
        }

        try {
            $this->validateAndDispatch($event);
        } catch (Exception $e) {
            Log::error('Eroare la dispatch pentru job webhook Stripe.', [
                'event_type' => $event->type ?? 'necunoscut',
                'error' => $e->getMessage()
            ]);
            return response('Eroare de procesare', 500);
        }

        return response('Procesat', 200);
    }

    public function validateAndDispatch($event): void
    {
        $eventId = $event->id ?? null;
        $type = $event->type ?? null;
        $dataObject = $event->data->object ?? null;

        if (!$eventId || !$type || !$dataObject) {
            Log::error('Structura eveniment invalida primita.');
            return;
        }

        \Stripe\Jobs\ProcessStripeWebhookJob::dispatch($eventId, $type, $dataObject);
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
