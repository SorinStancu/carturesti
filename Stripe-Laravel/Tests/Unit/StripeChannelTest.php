<?php
declare(strict_types=1);

namespace Stripe\Tests\Unit;

use App\Models\Order;
use App\Models\PaymentChannel;
use Stripe\Driver\Channel as StripeChannel;

class StripeChannelTest
{
    public function testPaymentRequestReturnsHtmlString()
    {
        $paymentChannel = new PaymentChannel();
        $paymentChannel->credentials = json_encode([
            'api_key' => 'pk_test_123',
            'api_secret' => 'sk_test_123',
        ]);
        $paymentChannel->currencies = json_encode([]);
        $paymentChannel->status = 'active';

        $order = new Order();
        $order->id = 1;
        $order->user_id = 1;
        $order->total_amount = 100;

        $channel = new StripeChannel($paymentChannel);

        $fakeStripeClient = new class {
            public $checkout;
            public function __construct()
            {
                $this->checkout = new class {
                    public $sessions;
                    public function __construct()
                    {
                        $this->sessions = new class {
                            public function create($params, $opts)
                            {
                                return (object)['id' => 'cs_test_123'];
                            }
                        };
                    }
                };
            }
        };

        $channel->setStripeClient($fakeStripeClient);

        $html = $channel->paymentRequest($order);
        $ok = is_string($html)
            && str_contains($html, 'stripe.redirectToCheckout')
            && str_contains($html, 'cs_test_123');
        if (!$ok) {
            throw new \RuntimeException('StripeChannelTest failed in root copy.');
        }
    }
}
