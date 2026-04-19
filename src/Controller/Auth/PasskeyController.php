<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Service\PasskeyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route('/passkey')]
class PasskeyController extends AbstractController
{
    public function __construct(private PasskeyService $passkeyService) {}

    // ── Registration (authenticated user adds a passkey) ───────────

    #[Route('/register/options', name: 'passkey_register_options', methods: ['POST'])]
    public function registerOptions(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $options = $this->passkeyService->generateRegistrationOptions($user);
        return new JsonResponse($options);
    }

    #[Route('/register/verify', name: 'passkey_register_verify', methods: ['POST'])]
    public function registerVerify(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['credential'])) {
            return new JsonResponse(['error' => 'Invalid request'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $deviceName = $data['name'] ?? 'My Passkey';
            $passkey = $this->passkeyService->verifyRegistration($user, $data['credential'], $deviceName);

            return new JsonResponse([
                'success' => true,
                'passkey' => [
                    'id' => $passkey->getId(),
                    'name' => $passkey->getName(),
                    'createdAt' => $passkey->getCreatedAt()->format('Y-m-d H:i'),
                ],
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    // ── Authentication (login with passkey) ────────────────────────

    #[Route('/authenticate/options', name: 'passkey_auth_options', methods: ['POST'])]
    public function authOptions(): JsonResponse
    {
        $options = $this->passkeyService->generateAuthenticationOptions();
        return new JsonResponse($options);
    }

    #[Route('/authenticate/verify', name: 'passkey_auth_verify', methods: ['POST'])]
    public function authVerify(
        Request $request,
        EventDispatcherInterface $dispatcher,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['credential'])) {
            return new JsonResponse(['error' => 'Invalid request'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $user = $this->passkeyService->verifyAuthentication($data['credential']);
            if (!$user) {
                return new JsonResponse(['error' => 'Unknown credential'], Response::HTTP_UNAUTHORIZED);
            }

            if (!$user->isActive()) {
                return new JsonResponse(['error' => 'Account deactivated'], Response::HTTP_FORBIDDEN);
            }

            // Programmatic login
            $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
            $this->container->get('security.token_storage')->setToken($token);

            $request->getSession()->set('_security_main', serialize($token));
            $request->getSession()->migrate(true);

            $event = new InteractiveLoginEvent($request, $token);
            $dispatcher->dispatch($event);

            // Role-based redirect URL
            $route = match (strtoupper($user->getRole() ?? '')) {
                'ADMIN' => 'app_dashboard',
                'EMPLOYER' => 'app_employer_dashboard',
                'TRAINER' => 'app_trainer_dashboard',
                default => 'app_workspace',
            };

            return new JsonResponse([
                'success' => true,
                'redirect' => $this->generateUrl($route),
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    // ── Manage passkeys ────────────────────────────────────────────

    #[Route('/list', name: 'passkey_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $passkeys = $this->passkeyService->getUserPasskeys($user);
        $data = array_map(fn($p) => [
            'id' => $p->getId(),
            'name' => $p->getName(),
            'createdAt' => $p->getCreatedAt()->format('Y-m-d H:i'),
            'lastUsedAt' => $p->getLastUsedAt()?->format('Y-m-d H:i'),
        ], $passkeys);

        return new JsonResponse($data);
    }

    #[Route('/delete/{id}', name: 'passkey_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $deleted = $this->passkeyService->deletePasskey($id, $user);
        return new JsonResponse(['success' => $deleted], $deleted ? 200 : 404);
    }
}
