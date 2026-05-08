<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Enum\ContractStatus;
use App\Finance\Entity\Contract;
use App\Finance\Entity\ContractDelivery;
use App\Finance\Entity\ContractDispute;
use App\Finance\Entity\EscrowTransaction;
use App\Enum\EscrowTransactionType;
use PHPUnit\Framework\TestCase;

final class FinanceContractTest extends TestCase
{
    public function testLifecycleTransitions(): void
    {
        $contract = new Contract();

        self::assertSame(ContractStatus::PENDING_FUNDING, $contract->getStatus());
        $contract->fundEscrow();
        self::assertSame(ContractStatus::ACTIVE, $contract->getStatus());
        self::assertNotNull($contract->getEscrowFundedAt());

        $contract->submitDelivery();
        self::assertSame(ContractStatus::SUBMITTED, $contract->getStatus());

        $contract->approveDelivery();
        self::assertSame(ContractStatus::APPROVED, $contract->getStatus());

        $contract->releaseEscrow();
        self::assertSame(ContractStatus::CLOSED, $contract->getStatus());
        self::assertNotNull($contract->getReleasedAt());
    }

    public function testInvalidTransitionThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        (new Contract())->submitDelivery();
    }

    public function testFinanceValueObjects(): void
    {
        $contract = new Contract();
        $delivery = (new ContractDelivery())->setContract($contract)->setTitle('Milestone')->setMessage('Done');
        $dispute = (new ContractDispute())->setContract($contract)->setReason('Scope issue')->resolve('Released after review');
        $transaction = (new EscrowTransaction())->setContract($contract)->setType(EscrowTransactionType::RELEASE)->setAmount('100.00');

        self::assertSame('Milestone', $delivery->getTitle());
        self::assertSame('resolved', $dispute->getStatus()->value);
        self::assertSame(EscrowTransactionType::RELEASE, $transaction->getType());
    }
}
