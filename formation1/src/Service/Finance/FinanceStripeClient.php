<?php

namespace App\Service\Finance;

use Stripe\PaymentIntent;
use Stripe\Stripe;

final class FinanceStripeClient
{
    public function __construct(
        private readonly string $secretKey,
    ) {
    }

    public function assertConfigured(): void
    {
        if ($this->secretKey === '' || str_contains($this->secretKey, 'YOUR_KEY')) {
            throw new \RuntimeException('Configurez STRIPE_SECRET_KEY dans .env.local.');
        }
    }

    /**
     * @param array<string, string> $metadata
     *
     * @return array{clientSecret: string|null, id: string}
     */
    public function createPaymentIntent(float $amount, string $currency, array $metadata): array
    {
        $this->assertConfigured();
        Stripe::setApiKey($this->secretKey);
        $minor = StripeMoney::toMinorUnits($amount, $currency);
        if ($minor < 1) {
            throw new \InvalidArgumentException('Montant trop faible pour Stripe.');
        }

        $intent = PaymentIntent::create([
            'amount' => $minor,
            'currency' => strtolower($currency),
            'payment_method_types' => ['card'],
            'metadata' => $metadata,
        ]);

        return [
            'clientSecret' => $intent->client_secret,
            'id' => $intent->id,
        ];
    }

    public function retrievePaymentIntent(string $id): PaymentIntent
    {
        $this->assertConfigured();
        Stripe::setApiKey($this->secretKey);

        return PaymentIntent::retrieve($id);
    }
}
