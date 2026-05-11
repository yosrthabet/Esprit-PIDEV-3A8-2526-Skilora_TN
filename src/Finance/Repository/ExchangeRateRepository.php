<?php

declare(strict_types=1);

namespace App\Finance\Repository;

use App\Enum\Currency;
use App\Finance\Entity\ExchangeRate;
use App\Finance\Service\ExchangeRateService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Service\Attribute\Required;

/** @extends ServiceEntityRepository<ExchangeRate> */
class ExchangeRateRepository extends ServiceEntityRepository
{
    private ?ExchangeRateService $rateService = null;

    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, ExchangeRate::class); }

    #[Required]
    public function setRateService(ExchangeRateService $rateService): void
    {
        $this->rateService = $rateService;
    }

    public function findLatestRate(Currency $base, Currency $quote): ?ExchangeRate
    {
        return $this->createQueryBuilder('r')
            ->where('r.baseCurrency = :base')
            ->andWhere('r.quoteCurrency = :quote')
            ->setParameter('base', $base)
            ->setParameter('quote', $quote)
            ->orderBy('r.effectiveAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function convert(string $amount, Currency $from, Currency $to): string
    {
        if ($from === $to) {
            return $amount;
        }

        $rate = $this->findLatestRate($from, $to);
        if ($rate !== null) {
            return bcmul($amount, $rate->getRate(), 2);
        }

        $inverse = $this->findLatestRate($to, $from);
        if ($inverse !== null && bccomp($inverse->getRate(), '0', 6) > 0) {
            return bcdiv($amount, $inverse->getRate(), 2);
        }

        if ($this->rateService !== null) {
            $fetched = $this->rateService->fetchRate($from, $to);
            if ($fetched !== null) {
                return bcmul($amount, $fetched, 2);
            }
        }

        return $amount;
    }
}
