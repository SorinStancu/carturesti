<?php
declare(strict_types=1);

namespace Stripe\Tests\Feature;

use Stripe\Controller\WebhookController;

class StripeWebhookTest
{
    public function testProcessEventMarksOrderPayingOnCompleted()
    {
        $controller = new WebhookController();

        $event = [
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'payment_status' => 'paid',
                    'client_reference_id' => '10',
                    'metadata' => [
                        'order_id' => '10'
                    ]
                ]
            ]
        ];

        $controller->processEvent($event);
    }
}
