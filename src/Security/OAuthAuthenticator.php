<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Provider\GithubResourceOwner;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class OAuthAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $em,
        private RouterInterface $router,
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'app_oauth_check';
    }

    public function authenticate(Request $request): Passport
    {
        $provider = $request->attributes->get('provider');
        $client = $this->clientRegistry->getClient($provider);
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client, $provider) {
                $oauthUser = $client->fetchUserFromToken($accessToken);

                return match ($provider) {
                    'google' => $this->handleGoogleUser($oauthUser),
                    'github' => $this->handleGithubUser($oauthUser),
                    default => throw new AuthenticationException('Unsupported OAuth provider.'),
                };
            })
        );
    }

    private function handleGoogleUser(GoogleUser $googleUser): User
    {
        $googleId = $googleUser->getId();
        $email = $googleUser->getEmail();

        // 1. Find by Google ID
        $user = $this->em->getRepository(User::class)->findOneBy(['googleId' => $googleId]);
        if ($user) {
            return $user;
        }

        // 2. Find by email and link
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($user) {
            $user->setGoogleId($googleId);
            $this->em->flush();
            return $user;
        }

        // 3. Create new user
        $user = new User();
        $user->setEmail($email);
        $user->setUsername($this->generateUsername($googleUser->getName() ?? $email));
        $user->setFullName($googleUser->getName());
        $user->setPhotoUrl($googleUser->getAvatar());
        $user->setGoogleId($googleId);
        $user->setRole('USER');
        $user->setVerified(true);
        $user->setActive(true);
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function handleGithubUser(GithubResourceOwner $githubUser): User
    {
        $githubId = (string) $githubUser->getId();
        $email = $githubUser->getEmail();

        // 1. Find by GitHub ID
        $user = $this->em->getRepository(User::class)->findOneBy(['githubId' => $githubId]);
        if ($user) {
            return $user;
        }

        // 2. Find by email and link (if email available)
        if ($email) {
            $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($user) {
                $user->setGithubId($githubId);
                $this->em->flush();
                return $user;
            }
        }

        // 3. No existing user — need registration with password
        throw new CustomUserMessageAuthenticationException('oauth_needs_registration', [
            'provider' => 'github',
            'github_id' => $githubId,
            'email' => $email,
            'nickname' => $githubUser->getNickname(),
            'full_name' => $githubUser->getName(),
            'avatar_url' => $githubUser->toArray()['avatar_url'] ?? null,
        ]);
    }

    private function generateUsername(string $base): string
    {
        $slug = preg_replace('/[^a-z0-9_]/', '', strtolower(str_replace(' ', '_', $base)));
        $slug = $slug ?: 'user';

        $existing = $this->em->getRepository(User::class)->findOneBy(['username' => $slug]);
        if (!$existing) {
            return $slug;
        }

        return $slug . '_' . bin2hex(random_bytes(3));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var User $user */
        $user = $token->getUser();

        $route = match (strtoupper($user->getRole() ?? '')) {
            'ADMIN' => 'app_dashboard',
            'EMPLOYER' => 'app_employer_dashboard',
            'TRAINER' => 'app_trainer_dashboard',
            default => 'app_workspace',
        };

        return new RedirectResponse($this->router->generate($route));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // New GitHub user — redirect to complete registration
        if ($exception instanceof CustomUserMessageAuthenticationException
            && $exception->getMessageKey() === 'oauth_needs_registration'
        ) {
            $data = $exception->getMessageData();
            $request->getSession()->set('oauth_registration', [
                'provider' => $data['provider'],
                'github_id' => $data['github_id'],
                'email' => $data['email'],
                'nickname' => $data['nickname'],
                'full_name' => $data['full_name'],
                'avatar_url' => $data['avatar_url'],
            ]);

            return new RedirectResponse($this->router->generate('app_oauth_complete_registration'));
        }

        $request->getSession()->getFlashBag()->add('error', 'OAuth authentication failed: ' . $exception->getMessageKey());

        return new RedirectResponse($this->router->generate('app_login'));
    }
}
