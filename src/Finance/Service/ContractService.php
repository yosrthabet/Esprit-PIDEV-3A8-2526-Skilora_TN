<?php

declare(strict_types=1);

namespace App\Finance\Service;

use App\Entity\User;
use App\Enum\EscrowTransactionType;
use App\Finance\Entity\Contract;
use App\Finance\Entity\ContractDelivery;
use App\Finance\Entity\ContractDispute;
use App\Finance\Entity\EscrowTransaction;
use App\Finance\Entity\Invoice;
use App\Finance\Repository\ContractRepository;
use App\Finance\Entity\PaymentTransaction;
use App\Finance\Entity\Wallet;
use App\Finance\Repository\InvoiceRepository;
use App\Finance\Repository\WalletRepository;
use App\Recruitment\Entity\HireOffer;
use Doctrine\ORM\EntityManagerInterface;

class ContractService
{
    public function __construct(
        private readonly ContractRepository $contractRepository,
        private readonly InvoiceRepository $invoiceRepository,
        private readonly WalletRepository $walletRepository,
        private readonly FinanceNotifier $notifier,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function createFromAcceptedOffer(HireOffer $hireOffer): Contract
    {
        $existing = $this->contractRepository->findOneByHireOffer($hireOffer);
        if ($existing !== null) {
            return $existing;
        }

        $contract = (new Contract())
            ->setHireOffer($hireOffer)
            ->setAmount($hireOffer->getSalaryOffered())
            ->setCurrency($hireOffer->getCurrency());
        $this->entityManager->persist($contract);
        $this->entityManager->flush();

        return $contract;
    }

    public function assertParticipant(User $user, Contract $contract): void
    {
        if ($contract->getEmployer()?->getId() !== $user->getId() && $contract->getFreelancer()->getId() !== $user->getId()) {
            throw new \RuntimeException('You cannot access this contract.');
        }
    }

    public function fundEscrow(User $employer, Contract $contract): void
    {
        if ($contract->getEmployer()?->getId() !== $employer->getId()) {
            throw new \RuntimeException('Only the employer can fund this contract.');
        }

        $contract->fundEscrow();
        $this->entityManager->persist($this->transaction($contract, EscrowTransactionType::FUND));
        $freelancer = $contract->getFreelancer();
        $this->notifier->notifyParticipant($freelancer, $contract, 'finance.funded', 'Escrow funded', 'Work can now begin.');
        $this->entityManager->flush();
    }

    public function submitDelivery(User $freelancer, Contract $contract, string $title, string $message, ?string $attachmentUrl): void
    {
        if ($contract->getFreelancer()->getId() !== $freelancer->getId()) {
            throw new \RuntimeException('Only the freelancer can submit delivery.');
        }
        $delivery = (new ContractDelivery())
            ->setContract($contract)
            ->setSubmittedBy($freelancer)
            ->setTitle($title)
            ->setMessage($message)
            ->setAttachmentUrl($attachmentUrl);
        $contract->submitDelivery();
        $this->entityManager->persist($delivery);
        if ($contract->getEmployer() !== null) {
            $this->notifier->notifyParticipant($contract->getEmployer(), $contract, 'finance.delivery_submitted', 'Delivery submitted', $title);
        }
        $this->entityManager->flush();
    }

    public function approveDelivery(User $employer, Contract $contract): void
    {
        if ($contract->getEmployer()?->getId() !== $employer->getId()) {
            throw new \RuntimeException('Only the employer can approve this delivery.');
        }
        $contract->approveDelivery();
        $this->notifier->notifyParticipant($contract->getFreelancer(), $contract, 'finance.delivery_approved', 'Delivery approved', 'The employer approved your delivery.');
        $this->entityManager->flush();
    }

    public function releaseEscrow(User $employer, Contract $contract): Invoice
    {
        if ($contract->getEmployer()?->getId() !== $employer->getId()) {
            throw new \RuntimeException('Only the employer can release escrow.');
        }
        $contract->releaseEscrow();
        $this->entityManager->persist($this->transaction($contract, EscrowTransactionType::RELEASE));
        $this->creditFreelancerWallet($contract);
        $invoice = $this->invoiceRepository->findOneForContract($contract) ?? $this->createInvoice($contract);
        $invoice->markPaid();
        $this->entityManager->persist($invoice);
        $this->notifier->notifyParticipant($contract->getFreelancer(), $contract, 'finance.escrow_released', 'Escrow released', 'The contract is now closed.');
        $this->entityManager->flush();

        return $invoice;
    }

    public function openDispute(User $user, Contract $contract, string $reason, ?string $details): void
    {
        $this->assertParticipant($user, $contract);
        $contract->openDispute();
        $dispute = (new ContractDispute())
            ->setContract($contract)
            ->setOpenedBy($user)
            ->setReason($reason)
            ->setDetails($details);
        $this->entityManager->persist($dispute);
        $counterparty = $contract->getFreelancer()->getId() === $user->getId() ? $contract->getEmployer() : $contract->getFreelancer();
        if ($counterparty !== null) {
            $this->notifier->notifyParticipant($counterparty, $contract, 'finance.dispute_opened', 'Contract dispute opened', $reason);
        }
        $this->notifier->notifyAdmins($contract, 'Contract dispute opened', $reason);
        $this->entityManager->flush();
    }

    public function resolveDispute(ContractDispute $dispute, string $resolution): void
    {
        $contract = $dispute->getContract();
        $dispute->resolve($resolution);
        $contract->closeFromDispute();
        $this->entityManager->persist($this->transaction($contract, EscrowTransactionType::RELEASE));
        $this->creditFreelancerWallet($contract);
        $this->entityManager->flush();
    }

    private function transaction(Contract $contract, EscrowTransactionType $type): EscrowTransaction
    {
        return (new EscrowTransaction())
            ->setContract($contract)
            ->setType($type)
            ->setAmount($contract->getAmount())
            ->setCurrency($contract->getCurrency());
    }

    private function creditFreelancerWallet(Contract $contract): void
    {
        $freelancer = $contract->getFreelancer();
        $amount = $contract->getAmount();
        if ($amount === null || !is_numeric($amount) || bccomp($amount, '0.00', 2) <= 0) {
            return;
        }

        $wallet = $this->walletRepository->findOneForUser($freelancer, $contract->getCurrency());
        if ($wallet === null) {
            $wallet = (new Wallet())
                ->setUser($freelancer)
                ->setCurrency($contract->getCurrency());
            $this->entityManager->persist($wallet);
        }
        $wallet->credit($amount);

        $tx = (new PaymentTransaction())
            ->setUser($freelancer)
            ->setType('escrow_release')
            ->setAmount($amount)
            ->setCurrency($contract->getCurrency())
            ->setProviderReference('contract-' . $contract->getId());
        $this->entityManager->persist($tx);
    }

    private function createInvoice(Contract $contract): Invoice
    {
        $employer = $contract->getEmployer();
        if ($employer === null) {
            throw new \RuntimeException('Contract employer is missing.');
        }

        return (new Invoice())
            ->setContract($contract)
            ->setNumber('INV-' . date('Ymd') . '-' . bin2hex(random_bytes(3)))
            ->setIssuer($contract->getFreelancer())
            ->setRecipient($employer)
            ->setAmount($contract->getAmount())
            ->setCurrency($contract->getCurrency());
    }
}
