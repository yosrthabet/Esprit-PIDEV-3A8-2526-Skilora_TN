<?php

namespace App\Validation\Finance;

/**
 * Valeurs autorisées alignées sur les ChoiceType Finance et le schéma métier.
 */
final class FinanceAllowedValues
{
    public const CURRENCIES = ['TND', 'EUR', 'USD'];

    public const CONTRACT_TYPES = ['PERMANENT', 'FREELANCE', 'INTERNSHIP', 'CDD', 'CDI'];

    public const CONTRACT_STATUSES = ['ACTIVE', 'EXPIRED', 'TERMINATED', 'PENDING'];

    public const PAYSLIP_STATUSES = ['DRAFT', 'PENDING', 'APPROVED', 'PAID'];
}
