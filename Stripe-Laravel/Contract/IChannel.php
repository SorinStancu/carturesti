<?php
declare(strict_types=1);

namespace Stripe\Contract;

use App\Models\Order;
use App\Models\PaymentChannel;
use Illuminate\Http\Request;

interface IChannel
{
    public function __construct(PaymentChannel $paymentChannel);

    public function paymentRequest(Order $order): string;

    public function verify(Request $request);

    public function getCredentialItems(): array;
}

