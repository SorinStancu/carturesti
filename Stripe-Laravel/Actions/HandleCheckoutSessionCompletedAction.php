<?php
declare(strict_types=1);

namespace Stripe\Actions;

use App\Models\Order;
use Stripe\Enums\PaymentStatus;
use Illuminate\Support\Facades\Log;
use Exception;

class HandleCheckoutSessionCompletedAction
{
    public function execute(object $session): void
    {
        try {
            Log::info('HandleCheckoutSessionCompletedAction Stripe pornit.', [
                'session_id' => $session->id ?? 'necunoscut',
            ]);

            $paymentStatus = $session->payment_status ?? null;
            $clientReferenceId = $session->client_reference_id ?? null;
                        
            $metadataOrderId = null;
            if (isset($session->metadata) && isset($session->metadata->order_id)) {
                $metadataOrderId = $session->metadata->order_id;
            }

            $orderId = $clientReferenceId ?? $metadataOrderId;

            if ($paymentStatus === PaymentStatus::PAID->value && $orderId) {
                Log::info('Plata primita pentru comanda.', ['order_id' => $orderId]);
                $this->updateOrder((int) $orderId);
            } else {
                Log::warning('Status plata neplatit sau lipsa ID comanda.', [
                    'payment_status' => $paymentStatus,
                    'order_id' => $orderId
                ]);
            }
        } catch (Exception $e) {
            Log::error('Eroare in executie HandleCheckoutSessionCompletedAction', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    protected function updateOrder(int $orderId): void
    {
        try {
            $order = Order::where('id', $orderId)->first();

            if (!$order) {
                Log::error('Comanda nu a fost gasita pentru actualizare.', ['order_id' => $orderId]);
                return;
            }

            if ($order->status !== Order::$paid) {
                $order->update(['status' => Order::$paying]);
                Log::info('Status comanda actualizat la paying.', ['order_id' => $orderId]);
            } else {
                Log::info('Comanda deja platita, se omite actualizarea.', ['order_id' => $orderId]);
            }
        } catch (Exception $e) {
            Log::error('Actualizarea statusului comenzii a esuat.', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            // Exceptie sau gestionare ca eroare critica in functie de cerinte
            throw $e;
        }
    }
}
