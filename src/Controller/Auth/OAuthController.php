<?php

namespace App\Controller\Auth;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OAuthController extends AbstractController
{
    public function __construct(private ClientRegistry $clientRegistry) {}

    #[Route('/oauth/connect/{provider}', name: 'app_oauth_connect')]
    public function connect(string $provider, Request $request): RedirectResponse
    {
        if (!in_array($provider, ['google', 'github'], true)) {
            throw $this->createNotFoundException('Unknown OAuth provider.');
        }

        // Check if credentials are configured
        try {
            $client = $this->clientRegistry->getClient($provider);
        } catch (\Exception $e) {
            $request->getSession()->getFlashBag()->add('error', ucfirst($provider) . ' login is not configured yet.');
            return new RedirectResponse($this->generateUrl('app_login'));
        }

        $scopes = match ($provider) {
            'google' => ['openid', 'email', 'profile'],
            'github' => ['user:email', 'read:user'],
        };

        return $client->redirect($scopes, []);
    }

    #[Route('/oauth/check/{provider}', name: 'app_oauth_check')]
    public function check(): Response
    {
        // This is handled by OAuthAuthenticator — should never reach here
        throw new \LogicException('This should be intercepted by the OAuth authenticator.');
    }
}
