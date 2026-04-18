<?php

namespace App\Controller\Admin\Finance;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Finance\FinanceStripeClient;
use App\Service\Finance\PaymentSuccessWhatsAppMessageFactory;
use App\Service\Finance\TwilioWhatsAppNotifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/finance/project-payment')]
#[IsGranted('ROLE_ADMIN')]
final class ProjectAdvancePaymentController extends AbstractController
{
    public function __construct(
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    #[Route('', name: 'admin_finance_project_payment', methods: ['GET'])]
    public function index(
        UserRepository $users,
        #[Autowire('%env(STRIPE_PUBLIC_KEY)%')]
        string $stripePublicKey,
        #[Autowire('%env(TWILIO_WHATSAPP_TO_DEFAULT)%')]
        string $twilioWhatsappToDefault,
    ): Response {
        return $this->render('admin/finance/project_payment/index.html.twig', [
            'page_title' => 'Paiement avance projet',
            'beneficiaries' => $users->findAllOrderedByName(),
            'stripe_public_key' => $stripePublicKey,
            'csrf_token' => $this->csrfTokenManager->getToken('finance_project_payment')->getValue(),
            'twilio_whatsapp_to_default' => $twilioWhatsappToDefault,
        ]);
    }

    #[Route('/intent', name: 'admin_finance_project_payment_intent', methods: ['POST'])]
    public function createIntent(Request $request, FinanceStripeClient $stripe, UserRepository $users): JsonResponse
    {
        $data = json_decode((string) $request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['error' => 'JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->isCsrfValid($data, $request)) {
            return $this->json(['error' => 'Session expirée (CSRF). Rechargez la page.'], Response::HTTP_FORBIDDEN);
        }

        $amount = (float) ($data['amount'] ?? 0);
        $currency = strtoupper(trim((string) ($data['currency'] ?? 'USD')));
        $projectRef = trim((string) ($data['projectRef'] ?? ''));
        $beneficiaryId = (int) ($data['beneficiaryId'] ?? 0);

        if ($amount <= 0 || $beneficiaryId < 1 || $projectRef === '') {
            return $this->json(['error' => 'Montant, référence projet et bénéficiaire sont obligatoires.'], Response::HTTP_BAD_REQUEST);
        }

        if ($currency === 'TND') {
            return $this->json([
                'error' => 'Le TND n’est pas activé sur ce compte Stripe (ajoutez un compte bancaire pour cette devise sur dashboard.stripe.com). Utilisez USD ou EUR pour les tests.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($currency === 'USD' && $amount < 0.5) {
            return $this->json(['error' => 'Montant minimum 0,50 USD en mode test Stripe.'], Response::HTTP_BAD_REQUEST);
        }

        if ($currency === 'EUR' && $amount < 0.5) {
            return $this->json(['error' => 'Montant minimum 0,50 EUR en mode test Stripe.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $users->find($beneficiaryId);
        if (!$user instanceof User) {
            return $this->json(['error' => 'Bénéficiaire introuvable.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $out = $stripe->createPaymentIntent($amount, $currency, [
                'beneficiary_name' => $user->getFullName() ?? $user->getUsername() ?? '',
                'beneficiary_id' => (string) $user->getId(),
                'project_ref' => $projectRef,
            ]);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'clientSecret' => $out['clientSecret'],
            'paymentIntentId' => $out['id'],
        ]);
    }

    #[Route('/confirm', name: 'admin_finance_project_payment_confirm', methods: ['POST'])]
    public function confirmWhatsApp(
        Request $request,
        FinanceStripeClient $stripe,
        TwilioWhatsAppNotifier $twilio,
        PaymentSuccessWhatsAppMessageFactory $messageFactory,
    ): JsonResponse {
        $data = json_decode((string) $request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['error' => 'JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->isCsrfValid($data, $request)) {
            return $this->json(['error' => 'Session expirée (CSRF).'], Response::HTTP_FORBIDDEN);
        }

        $paymentIntentId = trim((string) ($data['paymentIntentId'] ?? ''));
        $whatsappTo = trim((string) ($data['whatsappTo'] ?? ''));

        if ($paymentIntentId === '' || $whatsappTo === '') {
            return $this->json(['error' => 'Identifiant de paiement et numéro WhatsApp sont obligatoires.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $pi = $stripe->retrievePaymentIntent($paymentIntentId);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        if ($pi->status !== 'succeeded') {
            return $this->json(['error' => 'Le paiement n’est pas confirmé côté Stripe (statut : '.$pi->status.').'], Response::HTTP_BAD_REQUEST);
        }

        $at = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));

        $body = $messageFactory->build($pi, $at);

        try {
            $out = $twilio->sendWhatsAppAndOptionalSms($whatsappTo, $body);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Paiement OK, mais notification Twilio échouée : '.$e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'ok' => true,
            'message' => 'Notification Twilio acceptée.',
            'whatsapp_sid' => $out['whatsapp_sid'],
            'sms_sid' => $out['sms_sid'],
            'whatsapp_status' => $out['whatsapp_status'],
            'delivery_warning' => $out['delivery_warning'] ?? null,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function isCsrfValid(array $data, Request $request): bool
    {
        $token = $data['_token'] ?? $request->headers->get('X-CSRF-TOKEN');

        return $this->csrfTokenManager->isTokenValid(
            new CsrfToken('finance_project_payment', (string) $token)
        );
    }
}
