<?php

namespace App\Service;

use App\Entity\Finance\BankAccount;
use App\Entity\Finance\Bonus;
use App\Entity\Finance\Contract;
use App\Entity\Finance\Payslip;
use App\Entity\User;
use App\Repository\Finance\BankAccountRepository;
use App\Repository\Finance\BonusRepository;
use App\Repository\Finance\ContractRepository;
use App\Repository\Finance\PayslipRepository;
use App\Repository\UserRepository;
use App\Service\Finance\FinancePdfAiSummaryService;

final class FinanceAnalyticsService
{
    public function __construct(
        private readonly ContractRepository $contractRepository,
        private readonly BankAccountRepository $bankAccountRepository,
        private readonly BonusRepository $bonusRepository,
        private readonly PayslipRepository $payslipRepository,
        private readonly UserRepository $userRepository,
        private readonly FinancePdfAiSummaryService $financePdfAiSummaryService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getOverview(?int $userId = null): array
    {
        [$contracts, $bankAccounts, $bonuses, $payslips] = $this->loadFinanceCollections($userId);

        $activeContracts = 0;
        foreach ($contracts as $contract) {
            if (strtoupper((string) $contract->getStatus()) === 'ACTIVE') {
                ++$activeContracts;
            }
        }

        $bonusTotal = 0.0;
        foreach ($bonuses as $bonus) {
            $bonusTotal += (float) ($bonus->getAmount() ?? 0);
        }

        $grossTotal = 0.0;
        $netTotal = 0.0;
        foreach ($payslips as $payslip) {
            $grossTotal += $payslip->getEstimatedGross();
            $netTotal += $payslip->getEstimatedNet();
        }

        $trend = $this->buildMonthlyTrend($payslips);
        $employees = $this->buildEmployeeSummaries($contracts, $bankAccounts, $bonuses, $payslips, 10);

        return [
            'kpis' => [
                [
                    'label' => 'Contrats actifs',
                    'value' => $activeContracts,
                    'hint' => sprintf('%d contrats au total', count($contracts)),
                ],
                [
                    'label' => 'Comptes bancaires',
                    'value' => count($bankAccounts),
                    'hint' => sprintf('%d comptes vérifiés', count(array_filter($bankAccounts, static fn ($a) => $a->isVerified()))),
                ],
                [
                    'label' => 'Primes versées',
                    'value' => $bonusTotal,
                    'hint' => sprintf('%d enregistrements', count($bonuses)),
                ],
                [
                    'label' => 'Masse salariale nette',
                    'value' => $netTotal,
                    'hint' => sprintf('Brut estimé %.2f', $grossTotal),
                ],
            ],
            'monthlyTrend' => $trend,
            'employees' => $employees,
            'scopeUserId' => $userId,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getEmployeeSummary(int $userId): ?array
    {
        $user = $this->userRepository->find($userId);
        if (!$user instanceof User) {
            return null;
        }

        $overview = $this->getOverview();
        foreach ($overview['employees'] as $employee) {
            if ((int) $employee['userId'] === $userId) {
                return $employee;
            }
        }

        return [
            'userId' => $user->getId(),
            'fullName' => $user->getFullName(),
            'contractsCount' => 0,
            'bankAccountsCount' => 0,
            'bonusesCount' => 0,
            'bonusTotal' => 0.0,
            'payslipsCount' => 0,
            'latestEstimatedNet' => 0.0,
            'latestEstimatedGross' => 0.0,
            'latestCnss' => 0.0,
            'latestIrpp' => 0.0,
            'latestBaseSalary' => 0.0,
            'latestOvertimeTotal' => 0.0,
            'latestPeriod' => null,
        ];
    }

    /**
     * @return array<string, float>
     */
    public function calculateTaxes(float $grossMonthlySalary): array
    {
        $grossMonthlySalary = max(0.0, $grossMonthlySalary);
        $cnssEmployeeRate = 0.0918;
        $cnssEmployerRate = 0.165;

        $cnssEmployee = $grossMonthlySalary * $cnssEmployeeRate;
        $cnssEmployer = $grossMonthlySalary * $cnssEmployerRate;
        $taxableAnnual = max(0.0, ($grossMonthlySalary - $cnssEmployee) * 12);
        $irppBreakdown = $this->calculateProgressiveIrppBreakdown($taxableAnnual);
        $irppAnnual = $irppBreakdown['total_irpp'];
        $irppMonthly = $irppAnnual / 12;
        $netMonthly = max(0.0, $grossMonthlySalary - $cnssEmployee - $irppMonthly);

        return [
            'gross_monthly' => round($grossMonthlySalary, 2),
            'cnss_employee' => round($cnssEmployee, 2),
            'cnss_employer' => round($cnssEmployer, 2),
            'taxable_annual' => round($taxableAnnual, 2),
            'irpp_annual' => round($irppAnnual, 2),
            'irpp_monthly' => round($irppMonthly, 2),
            'net_monthly' => round($netMonthly, 2),
            'effective_tax_rate_percent' => $grossMonthlySalary > 0 ? round((($grossMonthlySalary - $netMonthly) / $grossMonthlySalary) * 100, 2) : 0.0,
            'irpp_breakdown' => $irppBreakdown['bands'],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getEmployeeReportData(int $userId): ?array
    {
        $user = $this->userRepository->find($userId);
        if (!$user instanceof User) {
            return null;
        }

        [$contracts, $bankAccounts, $bonuses, $payslips] = $this->loadFinanceCollections($userId);

        $latestContract = $contracts[0] ?? null;
        $latestPayslip = $payslips[0] ?? null;

        $bonusTotal = 0.0;
        foreach ($bonuses as $bonus) {
            $bonusTotal += (float) ($bonus->getAmount() ?? 0);
        }

        $summary = sprintf(
            "%s dispose de %d contrat(s), %d bulletin(s), %d prime(s) et %d compte(s) bancaire(s). Dernier net estimé: %s TND.",
            (string) $user->getFullName(),
            count($contracts),
            count($payslips),
            count($bonuses),
            count($bankAccounts),
            number_format((float) ($latestPayslip?->getEstimatedNet() ?? 0), 2, '.', ' ')
        );

        $reportData = [
            'generated_at' => new \DateTimeImmutable(),
            'employee' => [
                'id' => $user->getId(),
                'full_name' => $user->getFullName(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
            ],
            'summary' => $summary,
            'metrics' => [
                'contracts_count' => count($contracts),
                'bank_accounts_count' => count($bankAccounts),
                'bonuses_count' => count($bonuses),
                'bonus_total' => round($bonusTotal, 2),
                'payslips_count' => count($payslips),
                'latest_estimated_net' => round((float) ($latestPayslip?->getEstimatedNet() ?? 0), 2),
            ],
            'contracts' => array_map(fn (Contract $contract): array => [
                'type' => $contract->getType(),
                'status' => $contract->getStatus(),
                'position' => $contract->getPosition(),
                'salary' => (float) ($contract->getSalary() ?? 0),
                'start_date' => $contract->getStartDate()?->format('Y-m-d'),
                'end_date' => $contract->getEndDate()?->format('Y-m-d'),
                'tenure_label' => $contract->getTenureLabel(),
                'company' => $contract->getCompany()?->getName(),
            ], $contracts),
            'payslips' => array_map(static fn (Payslip $payslip): array => [
                'period' => sprintf('%04d-%02d', (int) $payslip->getYear(), (int) $payslip->getMonth()),
                'base_salary' => (float) ($payslip->getBaseSalary() ?? 0),
                'gross_estimated' => $payslip->getEstimatedGross(),
                'net_estimated' => $payslip->getEstimatedNet(),
                'status' => $payslip->getStatus(),
            ], $payslips),
            'bonuses' => array_map(static fn (Bonus $bonus): array => [
                'amount' => (float) ($bonus->getAmount() ?? 0),
                'reason' => $bonus->getReason(),
                'date_awarded' => $bonus->getDateAwarded()?->format('Y-m-d'),
            ], $bonuses),
            'bank_accounts' => array_map(static fn (BankAccount $account): array => [
                'bank_name' => $account->getBankName(),
                'iban' => $account->getIban(),
                'swift' => $account->getSwift(),
                'currency' => $account->getCurrency(),
                'is_primary' => $account->isPrimary(),
                'is_verified' => $account->isVerified(),
            ], $bankAccounts),
            'monthly_trend' => $this->buildMonthlyTrend($payslips),
            'tax_estimate_on_latest_salary' => $this->calculateTaxes((float) ($latestContract?->getSalary() ?? 0)),
        ];

        $reportData['ai_summary'] = $this->financePdfAiSummaryService->buildSummary($reportData);

        return $reportData;
    }

    /**
     * @param array<int, mixed> $payslips
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildMonthlyTrend(array $payslips): array
    {
        $monthly = [];
        foreach ($payslips as $payslip) {
            $key = sprintf('%04d-%02d', (int) $payslip->getYear(), (int) $payslip->getMonth());
            if (!isset($monthly[$key])) {
                $monthly[$key] = ['period' => $key, 'gross' => 0.0, 'net' => 0.0, 'count' => 0];
            }
            $monthly[$key]['gross'] += $payslip->getEstimatedGross();
            $monthly[$key]['net'] += $payslip->getEstimatedNet();
            ++$monthly[$key]['count'];
        }

        ksort($monthly);
        $monthly = array_values($monthly);

        if (count($monthly) > 12) {
            $monthly = array_slice($monthly, -12);
        }

        $mapped = array_map(static fn (array $row): array => [
            'period' => $row['period'],
            'gross' => round((float) $row['gross'], 2),
            'net' => round((float) $row['net'], 2),
            'count' => (int) $row['count'],
        ], $monthly);

        $maxGross = 0.0;
        foreach ($mapped as $row) {
            $maxGross = max($maxGross, (float) $row['gross']);
        }

        foreach ($mapped as &$row) {
            $row['bar_pct'] = $maxGross > 0.0 ? (int) round(100.0 * ((float) $row['gross']) / $maxGross) : 0;
        }
        unset($row);

        return $mapped;
    }

    /**
     * Données agrégées pour le tableau de bord employeur : toute l’équipe (données admin), KPIs, détail par employé.
     *
     * @return array<string, mixed>
     */
    public function getEmployerDashboardPayload(): array
    {
        [$contracts, $bankAccounts, $bonuses, $payslips] = $this->loadFinanceCollections(null);

        $members = $this->buildEmployeeSummaries($contracts, $bankAccounts, $bonuses, $payslips, null);
        usort($members, static fn (array $a, array $b): int => strcasecmp((string) $a['fullName'], (string) $b['fullName']));

        $payrollMass = 0.0;
        foreach ($members as $m) {
            $payrollMass += (float) ($m['latestEstimatedGross'] ?? 0);
        }

        $totalDisbursed = 0.0;
        foreach ($payslips as $p) {
            $totalDisbursed += $p->getEstimatedGross();
        }

        $bankAlerts = count(array_filter($bankAccounts, static fn (BankAccount $a): bool => !$a->isVerified()));

        $contractsByUser = [];
        foreach ($contracts as $contract) {
            $uid = $contract->getUser()?->getId();
            if (null === $uid) {
                continue;
            }
            if (!isset($contractsByUser[$uid])) {
                $contractsByUser[$uid] = [];
            }
            $contractsByUser[$uid][] = $contract;
        }

        $payslipsByUser = [];
        foreach ($payslips as $payslip) {
            $uid = $payslip->getUser()?->getId();
            if (null === $uid) {
                continue;
            }
            $payslipsByUser[$uid][] = $payslip;
        }
        foreach ($payslipsByUser as $uid => $list) {
            usort($list, static function (Payslip $a, Payslip $b): int {
                $pa = sprintf('%04d-%02d', (int) $a->getYear(), (int) $a->getMonth());
                $pb = sprintf('%04d-%02d', (int) $b->getYear(), (int) $b->getMonth());

                return $pb <=> $pa;
            });
            $payslipsByUser[$uid] = $list;
        }

        $bonusesByUser = [];
        foreach ($bonuses as $bonus) {
            $uid = $bonus->getUser()?->getId();
            if (null === $uid) {
                continue;
            }
            $bonusesByUser[$uid][] = $bonus;
        }

        $banksByUser = [];
        foreach ($bankAccounts as $account) {
            $uid = $account->getUser()?->getId();
            if (null === $uid) {
                continue;
            }
            $banksByUser[$uid][] = $account;
        }

        $details = [];
        foreach ($members as $member) {
            $uid = (int) $member['userId'];
            $latestContract = $this->pickLatestContract($contractsByUser[$uid] ?? []);

            $details[$uid] = [
                'member' => $member,
                'contract' => $latestContract ? [
                    'position' => $latestContract->getPosition(),
                    'type' => $latestContract->getType(),
                    'status' => $latestContract->getStatus(),
                    'start_date' => $latestContract->getStartDate()?->format('Y-m-d'),
                    'end_date' => $latestContract->getEndDate()?->format('Y-m-d'),
                    'tenure_label' => $latestContract->getTenureLabel(),
                    'salary' => (float) ($latestContract->getSalary() ?? 0),
                ] : null,
                'payslips' => array_map(static fn (Payslip $p): array => [
                    'period' => sprintf('%04d-%02d', (int) $p->getYear(), (int) $p->getMonth()),
                    'base_salary' => (float) ($p->getBaseSalary() ?? 0),
                    'gross_estimated' => $p->getEstimatedGross(),
                    'net_estimated' => $p->getEstimatedNet(),
                    'cnss' => $p->getComputedCnss(),
                    'irpp' => $p->getComputedIrpp(),
                    'status' => $p->getStatus(),
                ], $payslipsByUser[$uid] ?? []),
                'bonuses' => array_map(static fn (Bonus $b): array => [
                    'amount' => (float) ($b->getAmount() ?? 0),
                    'reason' => $b->getReason(),
                    'date_awarded' => $b->getDateAwarded()?->format('Y-m-d'),
                ], $bonusesByUser[$uid] ?? []),
                'bank_accounts' => array_map(static fn (BankAccount $a): array => [
                    'bank_name' => $a->getBankName(),
                    'iban' => $a->getIban(),
                    'currency' => $a->getCurrency(),
                    'is_verified' => $a->isVerified(),
                    'is_primary' => $a->isPrimary(),
                ], $banksByUser[$uid] ?? []),
            ];
        }

        $membersLite = array_map(static function (array $m): array {
            $name = (string) $m['fullName'];
            $initials = self::initialsFromFullName($name);

            return [
                'userId' => (int) $m['userId'],
                'fullName' => $name,
                'initials' => $initials,
                'roleHint' => null,
                'latestEstimatedNet' => (float) ($m['latestEstimatedNet'] ?? 0),
            ];
        }, $members);

        foreach ($membersLite as &$lite) {
            $uid = $lite['userId'];
            $c = $this->pickLatestContract($contractsByUser[$uid] ?? []);
            $lite['roleHint'] = $c?->getPosition();
        }
        unset($lite);

        return [
            'kpis' => [
                'headcount' => count($members),
                'payroll_mass' => round($payrollMass, 2),
                'total_disbursed' => round($totalDisbursed, 2),
                'bank_alerts' => $bankAlerts,
            ],
            'members' => $membersLite,
            'details' => $details,
        ];
    }

    /**
     * @param Contract[] $userContracts
     */
    private function pickLatestContract(array $userContracts): ?Contract
    {
        if ($userContracts === []) {
            return null;
        }
        usort($userContracts, static function (Contract $a, Contract $b): int {
            $sa = $a->getStartDate();
            $sb = $b->getStartDate();
            if ($sa === $sb) {
                return ($b->getId() ?? 0) <=> ($a->getId() ?? 0);
            }
            if (null === $sa) {
                return 1;
            }
            if (null === $sb) {
                return -1;
            }

            return $sb <=> $sa;
        });

        return $userContracts[0];
    }

    private static function initialsFromFullName(string $fullName): string
    {
        $fullName = trim($fullName);
        if ($fullName === '') {
            return '?';
        }
        $parts = preg_split('/\s+/u', $fullName) ?: [];
        $letters = '';
        foreach ($parts as $part) {
            if ($part !== '') {
                $letters .= strtoupper(mb_substr($part, 0, 1));
            }
            if (mb_strlen($letters) >= 2) {
                break;
            }
        }

        return $letters !== '' ? $letters : strtoupper(mb_substr($fullName, 0, 2));
    }

    /**
     * @param int|null $limit null = tous les employés (portail employeur)
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildEmployeeSummaries(array $contracts, array $bankAccounts, array $bonuses, array $payslips, ?int $limit = 10): array
    {
        $rows = [];

        $seed = function (int $userId, string $fullName) use (&$rows): void {
            if (!isset($rows[$userId])) {
                $rows[$userId] = [
                    'userId' => $userId,
                    'fullName' => $fullName,
                    'contractsCount' => 0,
                    'bankAccountsCount' => 0,
                    'bonusesCount' => 0,
                    'bonusTotal' => 0.0,
                    'payslipsCount' => 0,
                    'latestEstimatedNet' => 0.0,
                    'latestEstimatedGross' => 0.0,
                    'latestCnss' => 0.0,
                    'latestIrpp' => 0.0,
                    'latestBaseSalary' => 0.0,
                    'latestOvertimeTotal' => 0.0,
                    'latestPeriod' => null,
                ];
            }
        };

        foreach ($contracts as $contract) {
            $user = $contract->getUser();
            if (!$user instanceof User || null === $user->getId()) {
                continue;
            }
            $seed($user->getId(), (string) $user->getFullName());
            ++$rows[$user->getId()]['contractsCount'];
        }

        foreach ($bankAccounts as $bankAccount) {
            $user = $bankAccount->getUser();
            if (!$user instanceof User || null === $user->getId()) {
                continue;
            }
            $seed($user->getId(), (string) $user->getFullName());
            ++$rows[$user->getId()]['bankAccountsCount'];
        }

        foreach ($bonuses as $bonus) {
            $user = $bonus->getUser();
            if (!$user instanceof User || null === $user->getId()) {
                continue;
            }
            $seed($user->getId(), (string) $user->getFullName());
            ++$rows[$user->getId()]['bonusesCount'];
            $rows[$user->getId()]['bonusTotal'] += (float) ($bonus->getAmount() ?? 0);
        }

        foreach ($payslips as $payslip) {
            $user = $payslip->getUser();
            if (!$user instanceof User || null === $user->getId()) {
                continue;
            }
            $seed($user->getId(), (string) $user->getFullName());
            ++$rows[$user->getId()]['payslipsCount'];
            $period = sprintf('%04d-%02d', (int) $payslip->getYear(), (int) $payslip->getMonth());
            if ($rows[$user->getId()]['latestPeriod'] === null || $period > $rows[$user->getId()]['latestPeriod']) {
                $rows[$user->getId()]['latestPeriod'] = $period;
                $rows[$user->getId()]['latestEstimatedNet'] = $payslip->getEstimatedNet();
                $rows[$user->getId()]['latestEstimatedGross'] = $payslip->getEstimatedGross();
                $rows[$user->getId()]['latestCnss'] = $payslip->getComputedCnss();
                $rows[$user->getId()]['latestIrpp'] = $payslip->getComputedIrpp();
                $rows[$user->getId()]['latestBaseSalary'] = (float) ($payslip->getBaseSalary() ?? 0);
                $rows[$user->getId()]['latestOvertimeTotal'] = (float) ($payslip->getOvertimeTotal() ?? 0);
            }
        }

        foreach ($rows as &$row) {
            $row['bonusTotal'] = round((float) $row['bonusTotal'], 2);
            $row['latestEstimatedNet'] = round((float) $row['latestEstimatedNet'], 2);
            $row['latestEstimatedGross'] = round((float) $row['latestEstimatedGross'], 2);
            $row['latestCnss'] = round((float) $row['latestCnss'], 2);
            $row['latestIrpp'] = round((float) $row['latestIrpp'], 2);
            $row['latestBaseSalary'] = round((float) $row['latestBaseSalary'], 2);
            $row['latestOvertimeTotal'] = round((float) $row['latestOvertimeTotal'], 2);
        }
        unset($row);

        usort($rows, static fn (array $a, array $b): int => $b['latestEstimatedNet'] <=> $a['latestEstimatedNet']);

        if (null !== $limit) {
            $rows = array_slice($rows, 0, $limit);
        }

        return $rows;
    }

    /**
     * @return array{total_irpp: float, bands: array<int, array<string, float|null>>}
     */
    private function calculateProgressiveIrppBreakdown(float $taxableAnnual): array
    {
        $bands = [
            [0.0, 5000.0, 0.00],
            [5000.0, 20000.0, 0.26],
            [20000.0, 30000.0, 0.28],
            [30000.0, 50000.0, 0.32],
            [50000.0, null, 0.35],
        ];

        $taxableAnnual = max(0.0, $taxableAnnual);
        $tax = 0.0;
        $details = [];

        foreach ($bands as [$min, $max, $rate]) {
            if ($taxableAnnual <= $min) {
                continue;
            }

            $upperBound = $max ?? $taxableAnnual;
            $portion = min($taxableAnnual, $upperBound) - $min;
            $portionTax = 0.0;
            if ($portion > 0) {
                $portionTax = $portion * $rate;
                $tax += $portionTax;
            }

            $details[] = [
                'min' => $min,
                'max' => $max,
                'rate_percent' => $rate * 100,
                'taxable_amount' => round($portion, 2),
                'tax_amount' => round($portionTax, 2),
            ];
        }

        return [
            'total_irpp' => max(0.0, $tax),
            'bands' => $details,
        ];
    }

    /**
     * @return array{0: Contract[], 1: BankAccount[], 2: Bonus[], 3: Payslip[]}
     */
    private function loadFinanceCollections(?int $userId): array
    {
        /** @var Contract[] $contracts */
        $contracts = $this->contractRepository->findAllOrdered();
        /** @var BankAccount[] $bankAccounts */
        $bankAccounts = $this->bankAccountRepository->findAllOrdered();
        /** @var Bonus[] $bonuses */
        $bonuses = $this->bonusRepository->findAllOrdered();
        /** @var Payslip[] $payslips */
        $payslips = $this->payslipRepository->findAllOrdered();

        if (null === $userId) {
            return [$contracts, $bankAccounts, $bonuses, $payslips];
        }

        $contracts = array_values(array_filter($contracts, static fn (Contract $c): bool => $c->getUser()?->getId() === $userId));
        $bankAccounts = array_values(array_filter($bankAccounts, static fn (BankAccount $b): bool => $b->getUser()?->getId() === $userId));
        $bonuses = array_values(array_filter($bonuses, static fn (Bonus $b): bool => $b->getUser()?->getId() === $userId));
        $payslips = array_values(array_filter($payslips, static fn (Payslip $p): bool => $p->getUser()?->getId() === $userId));

        return [$contracts, $bankAccounts, $bonuses, $payslips];
    }
}
