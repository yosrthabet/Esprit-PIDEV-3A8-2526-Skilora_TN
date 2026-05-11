<?php

declare(strict_types=1);

namespace App\Service\Integration;

use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeService
{
    public function __construct(
        private readonly string $stripeSecretKey,
        private readonly string $stripePublicKey,
        private readonly string $stripeWebhookSecret,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->stripeSecretKey !== '' && $this->stripeSecretKey !== 'sk_test_PLACEHOLDER';
    }

    public function getPublicKey(): string
    {
        return $this->stripePublicKey;
    }

    /**
     * @param array{amount: int, currency: string, description: string, success_url: string, cancel_url: string, metadata?: array<string, string>} $params
     * @return array{id: string, url: string}
     */
    public function createCheckoutSession(array $params): array
    {
        if (!$this->isConfigured()) {
            $this->logger->warning('Stripe is not configured. Returning mock session.');
            return ['id' => 'cs_mock_' . bin2hex(random_bytes(8)), 'url' => $params['success_url']];
        }

        Stripe::setApiKey($this->stripeSecretKey);

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $params['currency'],
                    'unit_amount' => $params['amount'],
                    'product_data' => [
                        'name' => $params['description'],
                    ],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $params['success_url'] . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $params['cancel_url'],
            'metadata' => $params['metadata'] ?? [],
        ]);

        $this->logger->info('Stripe checkout session created', ['id' => $session->id]);

        return ['id' => $session->id, 'url' => $session->url ?? $params['success_url']];
    }

    /**
     * @return array<string, mixed>
     */
    public function constructWebhookEvent(string $payload, string $signature): array
    {
        if (!$this->isConfigured()) {
            return json_decode($payload, true) ?: [];
        }

        try {
            $event = Webhook::constructEvent($payload, $signature, $this->stripeWebhookSecret);
            return $event->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Stripe webhook verification failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        if (!$this->isConfigured()) {
            return true;
        }

        try {
            Webhook::constructEvent($payload, $signature, $this->stripeWebhookSecret);
            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
