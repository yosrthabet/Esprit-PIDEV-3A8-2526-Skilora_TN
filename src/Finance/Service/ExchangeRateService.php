<?php

declare(strict_types=1);

namespace App\Finance\Service;

use App\Enum\Currency;
use App\Finance\Entity\ExchangeRate;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExchangeRateService
{
    private const API_URL = 'https://open.er-api.com/v6/latest/';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function refreshAll(): int
    {
        $currencies = Currency::cases();
        $targets = array_map(fn(Currency $c) => $c->value, $currencies);
        $count = 0;

        foreach ($currencies as $base) {
            try {
                $response = $this->httpClient->request('GET', self::API_URL . $base->value, [
                    'timeout' => 15,
                ]);
                $data = $response->toArray();
                $rates = $data['rates'] ?? [];

                foreach ($currencies as $quote) {
                    if ($base === $quote) {
                        continue;
                    }
                    $rateValue = $rates[$quote->value] ?? null;
                    if ($rateValue !== null && (float) $rateValue > 0) {
                        $this->upsertRate($base, $quote, number_format((float) $rateValue, 6, '.', ''));
                        $count++;
                    }
                }
            } catch (\Throwable $e) {
                $this->logger->warning("Failed to fetch rates for {$base->value}: " . $e->getMessage());
            }
        }

        return $count;
    }

    public function fetchRate(Currency $from, Currency $to): ?string
    {
        if ($from === $to) {
            return '1.000000';
        }

        try {
            $response = $this->httpClient->request('GET', self::API_URL . $from->value, [
                'timeout' => 10,
            ]);
            $data = $response->toArray();
            $rateValue = $data['rates'][$to->value] ?? null;

            if ($rateValue !== null && (float) $rateValue > 0) {
                $rate = number_format((float) $rateValue, 6, '.', '');
                $this->upsertRate($from, $to, $rate);
                return $rate;
            }
        } catch (\Throwable $e) {
            $this->logger->warning("Failed to fetch {$from->value}/{$to->value} rate: " . $e->getMessage());
        }

        return null;
    }

    private function upsertRate(Currency $from, Currency $to, string $rate): void
    {
        $entity = (new ExchangeRate())
            ->setBaseCurrency($from)
            ->setQuoteCurrency($to)
            ->setRate($rate);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }
}
