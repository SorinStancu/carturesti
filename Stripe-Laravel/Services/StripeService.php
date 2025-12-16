<?php
declare(strict_types=1);

namespace Stripe\Services;

use Stripe\StripeClient;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use UnexpectedValueException;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Event;

class StripeService
{
    public function __construct(
        protected StripeClient $client
    ) {}

    public static function createWithSecret(string $secretKey): self
    {
        return new self(new StripeClient($secretKey));
    }

    /**
     * @param array $params
     * @param array|null $opts
     * @return CheckoutSession
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function createCheckoutSession(array $params, ?array $opts = null): CheckoutSession
    {
        return $this->client->checkout->sessions->create($params, $opts);
    }

    /**
     * @param string $payload
     * @param string $sigHeader
     * @param string $secret
     * @return Event
     * @throws SignatureVerificationException
     * @throws UnexpectedValueException
     */
    public function constructEvent(string $payload, string $sigHeader, string $secret): Event
    {
        return Webhook::constructEvent($payload, $sigHeader, $secret);
    }
}
