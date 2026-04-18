<?php

namespace App\Service\Finance;

use App\Entity\Finance\Payslip;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Appelle l’API de paiement et l’API SMS configurées via les variables d’environnement.
 * Les corps JSON sont fusionnés avec PAYMENT_EXTRA_JSON / SMS_EXTRA_JSON pour s’adapter à votre fournisseur.
 */
final class PayslipPaymentSmsService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $paymentApiUrl,
        private readonly string $paymentApiKey,
        private readonly string $paymentExtraJson,
        private readonly string $smsApiUrl,
        private readonly string $smsApiKey,
        private readonly string $smsExtraJson,
        private readonly string $smsSenderName,
    ) {
    }

    public function payAndNotify(Payslip $payslip, string $phoneRaw): PayAndNotifyResult
    {
        $messages = [];
        $paymentPreview = null;
        $smsPreview = null;

        $phone = $this->normalizePhone($phoneRaw);
        if ($phone === '' || strlen(preg_replace('/\D/', '', $phone)) < 8) {
            return new PayAndNotifyResult(false, ['Numéro de téléphone invalide.']);
        }

        if ($this->smsApiUrl === '') {
            return new PayAndNotifyResult(false, [
                'Définissez SMS_API_URL (et SMS_API_KEY si requis) dans .env.local pour envoyer le SMS.',
            ]);
        }

        $paymentOk = true;
        if ($this->paymentApiUrl !== '') {
            $base = [
                'amount' => round($payslip->getEstimatedNet(), 2),
                'currency' => $payslip->getCurrency() ?? 'TND',
                'reference' => 'payslip-'.$payslip->getId(),
                'description' => sprintf(
                    'Bulletin %02d/%d — %s',
                    (int) ($payslip->getMonth() ?? 0),
                    (int) ($payslip->getYear() ?? 0),
                    $payslip->getUser()?->getFullName() ?? ''
                ),
            ];
            $payload = $this->mergeJson($this->paymentExtraJson, $base);
            $options = ['json' => $payload, 'timeout' => 30];
            if ($this->paymentApiKey !== '') {
                $options['headers']['Authorization'] = 'Bearer '.$this->paymentApiKey;
            }
            try {
                $response = $this->httpClient->request('POST', $this->paymentApiUrl, $options);
                $status = $response->getStatusCode();
                $paymentPreview = $this->truncate((string) $response->getContent(false), 500);
                if ($status < 200 || $status >= 300) {
                    $paymentOk = false;
                    $messages[] = 'Paiement : réponse HTTP '.$status.'.';
                }
            } catch (\Throwable $e) {
                $paymentOk = false;
                $messages[] = 'Paiement : '.$e->getMessage();
            }
        }

        if (!$paymentOk) {
            return new PayAndNotifyResult(false, $messages, $paymentPreview, null);
        }

        $smsText = $this->buildSmsMessage($payslip);
        $smsBase = [
            'to' => $phone,
            'phone' => $phone,
            'message' => $smsText,
            'text' => $smsText,
            'sender' => $this->smsSenderName,
            'sender_id' => $this->smsSenderName,
        ];
        $smsPayload = $this->mergeJson($this->smsExtraJson, $smsBase);
        $smsOptions = ['json' => $smsPayload, 'timeout' => 30];
        if ($this->smsApiKey !== '') {
            $smsOptions['headers']['Authorization'] = 'Bearer '.$this->smsApiKey;
        }

        try {
            $smsResponse = $this->httpClient->request('POST', $this->smsApiUrl, $smsOptions);
            $smsStatus = $smsResponse->getStatusCode();
            $smsPreview = $this->truncate((string) $smsResponse->getContent(false), 500);
            if ($smsStatus < 200 || $smsStatus >= 300) {
                $messages[] = 'SMS : réponse HTTP '.$smsStatus.'.';

                return new PayAndNotifyResult(false, $messages, $paymentPreview, $smsPreview);
            }
        } catch (\Throwable $e) {
            $messages[] = 'SMS : '.$e->getMessage();

            return new PayAndNotifyResult(false, $messages, $paymentPreview, null);
        }

        return new PayAndNotifyResult(true, ['Paiement traité et SMS envoyé.'], $paymentPreview, $smsPreview);
    }

    private function buildSmsMessage(Payslip $payslip): string
    {
        $name = $payslip->getUser()?->getFullName() ?? 'Employé';
        $net = number_format($payslip->getEstimatedNet(), 2, ',', ' ');
        $cur = $payslip->getCurrency() ?? 'TND';
        $period = sprintf('%02d/%04d', (int) ($payslip->getMonth() ?? 0), (int) ($payslip->getYear() ?? 0));

        $line = 'Skilora — Bulletin '.$period.' — '.$name.'. Net : '.$net.' '.$cur.'. Ref bulletin #'.$payslip->getId().'.';

        return $this->truncate($line, 480);
    }

    private function mergeJson(string $extraJson, array $base): array
    {
        $trim = trim($extraJson);
        if ($trim === '') {
            return $base;
        }
        $decoded = json_decode($trim, true);
        if (!\is_array($decoded)) {
            return $base;
        }

        return array_merge($base, $decoded);
    }

    private function normalizePhone(string $raw): string
    {
        $d = preg_replace('/\s+/', '', trim($raw));
        if ($d === '') {
            return '';
        }
        if (str_starts_with($d, '+')) {
            return $d;
        }
        if (str_starts_with($d, '00')) {
            return '+'.substr($d, 2);
        }
        $digits = preg_replace('/\D/', '', $d);
        if ($digits === null || $digits === '') {
            return $d;
        }
        if (str_starts_with($d, '0') && \strlen($digits) === 9) {
            return '+216'.substr($digits, 1);
        }
        if (\strlen($digits) === 8) {
            return '+216'.$digits;
        }

        return str_starts_with($d, '+') ? $d : '+'.$digits;
    }

    private function truncate(string $s, int $max): string
    {
        if (strlen($s) <= $max) {
            return $s;
        }

        return substr($s, 0, $max - 3).'...';
    }
}
