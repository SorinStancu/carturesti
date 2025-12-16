<?php
declare(strict_types=1);

namespace Stripe\Driver;

use App\Models\Order;
use App\Models\PaymentChannel;
use App\PaymentChannels\BasePaymentChannel;
use Stripe\Contract\IChannel;
use Stripe\Services\StripeService;

class Channel extends BasePaymentChannel implements IChannel
{
    protected string $currency;
    protected bool $test_mode = false;
    protected string $api_key;
    protected string $api_secret;
    protected string $order_session_key;
    protected ?StripeService $stripeService = null;

    protected array $credentialItems = [
        'api_key',
        'api_secret',
    ];

    public function __construct(PaymentChannel $paymentChannel)
    {
        $this->currency = currency();
        $this->order_session_key = 'stripe.payments.order_id';
        $this->setCredentialItems($paymentChannel);
    }

    public function paymentRequest(Order $order): string
    {
        $service = $this->getService();
        $generalSettings = getGeneralSettings();
        
        $payload = [
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $this->currency,
                    'unit_amount' => $this->calculateMinorAmount($order->total_amount),
                    'product_data' => [
                        'name' => ($generalSettings['site_name'] ?? 'Plata') . ' plata',
                    ],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->makeCallbackUrl('success'),
            'cancel_url' => $this->makeCallbackUrl('cancel'),
            'client_reference_id' => (string) $order->id,
            'metadata' => [
                'order_id' => (string) $order->id,
                'user_id' => (string) $order->user_id,
            ],
        ];

        $checkout = $service->createCheckoutSession(
            $payload,
            ['idempotency_key' => 'order-' . $order->id]
        );

        session()->put($this->order_session_key, $order->id);

        return $this->generateRedirectHtml($checkout->id);
    }

    private function calculateMinorAmount(float|int $amount): int
    {
        $formattedAmount = $this->makeAmountByCurrency($amount, $this->currency);
        return (int) round($formattedAmount * 100);
    }

    private function generateRedirectHtml(string $sessionId): string
    {
        return sprintf(
            '<script src="https://js.stripe.com/v3/"></script>
            <script type="text/javascript">
                let stripe = Stripe("%s");
                stripe.redirectToCheckout({ sessionId: "%s" });
            </script>',
            $this->api_key,
            $sessionId
        );
    }

    private function makeCallbackUrl(string $status): string
    {
        return url("/payments/verify/Stripe?status=$status&session_id={CHECKOUT_SESSION_ID}");
    }

    public function setStripeService(StripeService $service): void
    {
        $this->stripeService = $service;
    }

    /**
     * Set legacy client for tests (adapter for old tests if they inject raw StripeClient)
     * Ideally, tests should be updated to use setStripeService.
     */
    public function setStripeClient($client): void
    {
        $this->stripeService = new StripeService($client);
    }

    /**
     * @return StripeService
     */
    protected function getService(): StripeService
    {
        if (!$this->stripeService) {
            $this->stripeService = StripeService::createWithSecret($this->api_secret);
        }
        return $this->stripeService;
    }
}
