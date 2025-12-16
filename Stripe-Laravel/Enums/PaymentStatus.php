<?php
declare(strict_types=1);

namespace Stripe\Enums;

enum PaymentStatus: string
{
    case PAID = 'paid';
    case UNPAID = 'unpaid';
    case NO_PAYMENT_REQUIRED = 'no_payment_required';
}
