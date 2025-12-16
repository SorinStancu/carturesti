<?php
declare(strict_types=1);

namespace Stripe\Enums;

enum StripeEventType: string
{
    case CHECKOUT_SESSION_COMPLETED = 'checkout.session.completed';
    case CHARGE_SUCCEEDED = 'charge.succeeded';
    case PAYMENT_INTENT_SUCCEEDED = 'payment_intent.succeeded';
}
