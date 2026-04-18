<?php

namespace App\Service\Finance;

use Stripe\PaymentIntent;

/**
 * Modèle de message WhatsApp aligné sur l’aperçu Skilora (gras WhatsApp avec *...*).
 */
final class PaymentSuccessWhatsAppMessageFactory
{
    public function build(PaymentIntent $pi, \DateTimeInterface $at): string
    {
        $meta = $pi->metadata;
        $name = (string) ($meta['beneficiary_name'] ?? '—');
        $project = (string) ($meta['project_ref'] ?? '—');
        $minor = (int) $pi->amount;
        $currency = (string) $pi->currency;
        $floatAmount = StripeMoney::fromMinorUnits($minor, $currency);
        $amountLine = StripeMoney::formatDisplay($floatAmount, $currency);
        $dateStr = $at->format('d/m/Y').' à '.$at->format('H:i');
        $tx = $pi->id;

        return <<<MSG
*✅ PAIEMENT ATTRIBUÉ AVEC SUCCÈS*

👤 *Bénéficiaire :* {$name}
💰 *Montant :* {$amountLine}
📄 *Projet :* {$project}
📅 *Date :* {$dateStr}
🆔 *Réf. TX :* {$tx}

— Support Skilora Finance
MSG;
    }
}
