<?php

declare(strict_types=1);

namespace App\Finance\Repository;

use App\Entity\User;
use App\Enum\InvoiceStatus;
use App\Finance\Entity\Contract;
use App\Finance\Entity\Invoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Invoice> */
class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, Invoice::class); }

    public function findOneForContract(Contract $contract): ?Invoice
    {
        return $this->findOneBy(['contract' => $contract]);
    }

    /** @return list<Invoice> */
    public function findForUser(User $user): array
    {
        /** @var list<Invoice> $invoices */
        $invoices = $this->createQueryBuilder('i')
            ->join('i.contract', 'c')->addSelect('c')
            ->join('c.hireOffer', 'h')->addSelect('h')
            ->join('h.application', 'a')->addSelect('a')
            ->join('a.jobOffer', 'j')->addSelect('j')
            ->leftJoin('j.company', 'co')->addSelect('co')
            ->where('i.issuer = :user OR i.recipient = :user OR a.candidate = :user OR co.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $invoices;
    }

    public function sumPaidForUser(User $user): float
    {
        $total = 0.0;
        foreach ($this->findForUser($user) as $invoice) {
            if ($invoice->getStatus() === InvoiceStatus::PAID) {
                $total += (float) $invoice->getAmount();
            }
        }

        return $total;
    }

    public function isVisibleToUser(Invoice $invoice, User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $userId = $user->getId();
        $contract = $invoice->getContract();

        return $invoice->getIssuer()->getId() === $userId
            || $invoice->getRecipient()->getId() === $userId
            || $contract->getFreelancer()->getId() === $userId
            || $contract->getEmployer()?->getId() === $userId;
    }
}
