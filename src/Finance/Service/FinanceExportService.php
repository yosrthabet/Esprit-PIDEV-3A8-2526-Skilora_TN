<?php

declare(strict_types=1);

namespace App\Finance\Service;

use App\Entity\User;
use App\Finance\Entity\Contract;
use App\Finance\Repository\ContractRepository;
use App\Finance\Repository\EscrowTransactionRepository;
use App\Finance\Repository\InvoiceRepository;

class FinanceExportService
{
    public function __construct(
        private readonly ContractRepository $contractRepository,
        private readonly InvoiceRepository $invoiceRepository,
        private readonly EscrowTransactionRepository $transactionRepository,
    ) {
    }

    /**
     * @return array{headers: list<string>, rows: list<list<string>>}
     */
    public function contractsCsv(User $user): array
    {
        $contracts = $this->contractRepository->findForUser($user);
        $rows = [];
        foreach ($contracts as $c) {
            $jobTitle = $c->getHireOffer()->getApplication()->getJobOffer()->getTitle();
            $rows[] = [
                (string) ($c->getId() ?? ''),
                $jobTitle,
                $c->getStatus()->value,
                $c->getAmount() ?? '0',
                $c->getCurrency()->value,
                $c->getCreatedAt()->format('Y-m-d H:i'),
            ];
        }

        return [
            'headers' => ['ID', 'Title', 'Status', 'Amount', 'Currency', 'Created'],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{headers: list<string>, rows: list<list<string>>}
     */
    public function invoicesCsv(User $user): array
    {
        $invoices = $this->invoiceRepository->findForUser($user);
        $rows = [];
        foreach ($invoices as $inv) {
            $rows[] = [
                $inv->getNumber(),
                $inv->getStatus()->value,
                $inv->getAmount() ?? '0',
                $inv->getCurrency()->value,
                $inv->getIssuedAt()->format('Y-m-d'),
            ];
        }

        return [
            'headers' => ['Invoice #', 'Status', 'Amount', 'Currency', 'Issued'],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{headers: list<string>, rows: list<list<string>>}
     */
    public function transactionsCsv(User $user): array
    {
        $txns = $this->transactionRepository->findForUser($user, 500);
        $rows = [];
        foreach ($txns as $tx) {
            $rows[] = [
                (string) ($tx->getId() ?? ''),
                $tx->getType()->value,
                $tx->getAmount() ?? '0',
                $tx->getCurrency()->value,
                $tx->getCreatedAt()->format('Y-m-d H:i'),
            ];
        }

        return [
            'headers' => ['ID', 'Type', 'Amount', 'Currency', 'Date'],
            'rows' => $rows,
        ];
    }

    /**
     * @param array{headers: list<string>, rows: list<list<string>>} $data
     */
    public function toCsvString(array $data): string
    {
        $output = fopen('php://temp', 'r+');
        if ($output === false) {
            return '';
        }
        fputcsv($output, $data['headers']);
        foreach ($data['rows'] as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv ?: '';
    }
}
