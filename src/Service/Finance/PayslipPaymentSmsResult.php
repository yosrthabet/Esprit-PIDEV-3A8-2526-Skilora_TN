<?php

namespace App\Service\Finance;

final class PayslipPaymentSmsResult
{
    public function __construct(
        public readonly bool $paymentAttempted,
        public readonly bool $paymentOk,
        public readonly ?string $paymentDetail,
        public readonly bool $smsAttempted,
        public readonly bool $smsOk,
        public readonly ?string $smsDetail,
    ) {
    }

    public function isFullyOk(): bool
    {
        $paymentPart = !$this->paymentAttempted || $this->paymentOk;
        $smsPart = !$this->smsAttempted || $this->smsOk;

        return $paymentPart && $smsPart;
    }
}
