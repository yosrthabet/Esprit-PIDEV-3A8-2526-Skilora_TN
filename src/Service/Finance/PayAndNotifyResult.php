<?php

namespace App\Service\Finance;

/**
 * Résultat de l’appel paiement + SMS pour un bulletin.
 */
final readonly class PayAndNotifyResult
{
    /**
     * @param list<string> $messages Messages d’erreur ou d’information
     */
    public function __construct(
        public bool $success,
        public array $messages = [],
        public ?string $paymentResponsePreview = null,
        public ?string $smsResponsePreview = null,
    ) {
    }
}
