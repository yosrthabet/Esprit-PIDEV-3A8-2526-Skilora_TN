<?php

namespace App\Twig;

use App\Enum\WorkType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private readonly Security $security,
        private readonly RouterInterface $router,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('json_decode', [$this, 'jsonDecode']),
            new TwigFilter('work_type_label', [$this, 'workTypeLabel']),
            new TwigFilter('clean_job_description', [$this, 'cleanJobDescription']),
        ];
    }

    public function cleanJobDescription(?string $description): string
    {
        $text = $description ?? '';
        $text = preg_replace('/\[([^\]]+)]\((https?:\/\/[^\s)]+)\)/', '$1', $text) ?? $text;
        $text = preg_replace('/https?:\/\/\S+/', '', $text) ?? $text;
        $text = str_replace(['🔥', '⬇️', '👇'], '', $text);
        $text = preg_replace('/\bAPPLY HERE\b/i', '', $text) ?? $text;
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    public function workTypeLabel(mixed $value): string
    {
        if ($value instanceof WorkType) {
            return match ($value) {
                WorkType::REMOTE => 'Télétravail',
                WorkType::ONSITE => 'Présentiel',
                WorkType::HYBRID => 'Hybride',
            };
        }
        $stringValue = is_scalar($value) ? (string) $value : '';

        return match ($stringValue) {
            'remote' => 'Télétravail',
            'onsite' => 'Présentiel',
            'hybrid' => 'Hybride',
            default  => ucfirst($stringValue),
        };
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('skilora_nav', [$this, 'getSkiloraNav']),
        ];
    }

    /** @return array<mixed> */
    public function jsonDecode(string $string): array
    {
        $decoded = json_decode($string, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Centralized navigation config for all roles.
     * Returns ['user_role', 'dashboard_route', 'nav_items', 'topnav_items'].
     */
    /** @return array<string, mixed> */
    public function getSkiloraNav(): array
    {
        $user = $this->security->getUser();
        $role = ($user instanceof \App\Entity\User) ? strtoupper((string) $user->getRole()) : '';

        $dashboardRoute = match ($role) {
            'ADMIN'   => 'app_dashboard',
            'TRAINER' => 'app_trainer_dashboard',
            default   => 'app_workspace',
        };

        $navItems = match ($role) {
            'ADMIN' => [
                ['label' => 'Tableau de bord', 'route' => 'app_dashboard',   'icon' => 'layout-dashboard'],
                ['label' => 'separator'],
                ['label' => 'Recrutement',     'route' => 'app_jobs', 'icon' => 'briefcase'],
                ['label' => 'Finance',         'route' => 'app_admin_finance', 'icon' => 'wallet'],
                ['label' => 'separator'],
                ['label' => 'Support',           'route' => 'app_support',          'icon' => 'life-buoy'],
                ['label' => 'Communauté',        'route' => 'app_admin_community',  'icon' => 'message-circle'],
                ['label' => 'separator'],
                ['label' => 'Mon profil',        'route' => 'app_profile',          'icon' => 'user'],
                ['label' => 'Notifications',     'route' => 'app_notifications_index', 'icon' => 'bell'],
            ],
            'EMPLOYER' => [
                ['label' => 'Tableau de bord',   'route' => 'app_workspace',        'icon' => 'layout-dashboard'],
                ['label' => 'separator'],
                ['label' => 'Publier une offre', 'route' => 'app_post_job',         'icon' => 'plus-circle'],
                ['label' => 'Mes offres',        'route' => 'app_active_offers',    'icon' => 'briefcase'],
                ['label' => 'Candidatures',      'route' => 'app_applications',     'icon' => 'inbox'],
                ['label' => 'Entretiens',        'route' => 'app_interviews',       'icon' => 'video'],
                ['label' => 'Messagerie',        'route' => 'app_inbox',            'icon' => 'messages-square'],
                ['label' => 'separator'],
                ['label' => 'Mon profil',        'route' => 'app_profile',          'icon' => 'user'],
                ['label' => 'Notifications',     'route' => 'app_notifications_index', 'icon' => 'bell'],
                ['label' => 'Support',           'route' => 'app_support',          'icon' => 'life-buoy'],
                ['label' => 'Communauté',        'route' => 'app_community',        'icon' => 'message-circle'],
            ],
            'TRAINER' => [
                ['label' => 'Tableau de bord',   'route' => 'app_trainer_dashboard', 'icon' => 'layout-dashboard'],
                ['label' => 'separator'],
                ['label' => 'Mon profil',        'route' => 'app_profile',           'icon' => 'user'],
                ['label' => 'Messagerie',        'route' => 'app_inbox',             'icon' => 'messages-square'],
                ['label' => 'Notifications',     'route' => 'app_notifications_index', 'icon' => 'bell'],
                ['label' => 'Support',           'route' => 'app_support',           'icon' => 'life-buoy'],
                ['label' => 'Communauté',        'route' => 'app_community',         'icon' => 'message-circle'],
            ],
            default => [ // USER / FREELANCER
                ['label' => 'Accueil',           'route' => 'app_workspace',         'icon' => 'home'],
                ['label' => 'separator'],
                ['label' => 'Trouver un emploi', 'route' => 'app_jobs',              'icon' => 'search'],
                ['label' => 'Mes candidatures',  'route' => 'app_applications',      'icon' => 'file-text'],
                ['label' => 'Préférences emploi','route' => 'app_candidate_job_preferences', 'icon' => 'sliders-horizontal'],
                ['label' => 'Messagerie',        'route' => 'app_inbox',             'icon' => 'inbox'],
                ['label' => 'separator'],
                ['label' => 'Mon profil',        'route' => 'app_profile',           'icon' => 'user'],
                ['label' => 'Notifications',     'route' => 'app_notifications_index', 'icon' => 'bell'],
                ['label' => 'separator'],
                ['label' => 'Paramètres',        'route' => 'app_settings',          'icon' => 'settings'],
                ['label' => 'Support',           'route' => 'app_support',           'icon' => 'life-buoy'],
                ['label' => 'Communauté',        'route' => 'app_community',         'icon' => 'message-circle'],
            ],
        };

        $formationRoute = match ($role) {
            'ADMIN' => $this->firstExistingRoute(['app_admin_formations', 'app_formations']),
            'TRAINER' => $this->firstExistingRoute(['app_trainer_formations', 'app_formations']),
            default => $this->firstExistingRoute(['app_formations']),
        };
        if ($formationRoute !== null) {
            $navItems[] = ['label' => 'Formations', 'route' => $formationRoute, 'icon' => 'graduation-cap'];
        }
        $learningRoute = $this->firstExistingRoute(['app_learning']);
        if ($learningRoute !== null && $role !== 'ADMIN') {
            $navItems[] = ['label' => 'Learning', 'route' => $learningRoute, 'icon' => 'book-open'];
        }

        $topnavItems = array_values(array_filter($navItems, fn(array $item) => $item['label'] !== 'separator'));

        // Grouped nav for top-nav bar: primary links + dropdown groups
        $topnavGrouped = match ($role) {
            'ADMIN' => [
                'primary' => [
                    ['label' => 'Tableau de bord', 'route' => 'app_dashboard',   'icon' => 'layout-dashboard'],
                    ['label' => 'Recrutement',     'route' => 'app_jobs', 'icon' => 'briefcase'],
                    ['label' => 'Support',         'route' => 'app_admin_support',     'icon' => 'life-buoy'],
                ],
                'groups' => [
                    [
                        'label' => 'Modération', 'icon' => 'shield', 'children' => [
                            ['label' => 'Communauté', 'route' => 'app_admin_community', 'icon' => 'message-circle'],
                        ],
                    ],
                ],
            ],
            'EMPLOYER' => [
                'primary' => [
                    ['label' => 'Tableau de bord',   'route' => 'app_workspace',     'icon' => 'layout-dashboard'],
                    ['label' => 'Mes offres',        'route' => 'app_active_offers', 'icon' => 'briefcase'],
                    ['label' => 'Candidatures',      'route' => 'app_applications',  'icon' => 'inbox'],
                    ['label' => 'Entretiens',        'route' => 'app_interviews',    'icon' => 'video'],
                    ['label' => 'Contrats',          'route' => 'app_contracts',     'icon' => 'file-signature'],
                ],
                'groups' => [
                    [
                        'label' => 'Plus', 'icon' => 'grid', 'children' => [
                            ['label' => 'Publier une offre', 'route' => 'app_post_job', 'icon' => 'plus-circle'],
                            ['label' => 'Support',           'route' => 'app_support',  'icon' => 'life-buoy'],
                            ['label' => 'Communauté',        'route' => 'app_community', 'icon' => 'message-circle'],
                            ['label' => 'Messagerie',        'route' => 'app_inbox', 'icon' => 'messages-square'],
                        ],
                    ],
                ],
            ],
            'TRAINER' => [
                'primary' => [
                    ['label' => 'Tableau de bord', 'route' => 'app_trainer_dashboard', 'icon' => 'layout-dashboard'],
                    ['label' => 'Mon profil',      'route' => 'app_profile',           'icon' => 'user'],
                    ['label' => 'Support',         'route' => 'app_support',           'icon' => 'life-buoy'],
                    ['label' => 'Communauté',      'route' => 'app_community',         'icon' => 'message-circle'],
                    ['label' => 'Messagerie',      'route' => 'app_inbox',             'icon' => 'messages-square'],
                ],
                'groups' => [],
            ],
            default => [ // USER / FREELANCER
                'primary' => [
                    ['label' => 'Accueil',           'route' => 'app_workspace',   'icon' => 'home'],
                    ['label' => 'Trouver un emploi', 'route' => 'app_jobs',        'icon' => 'search'],
                    ['label' => 'Candidatures',      'route' => 'app_applications','icon' => 'file-text'],
                    ['label' => 'Préférences',       'route' => 'app_candidate_job_preferences', 'icon' => 'sliders-horizontal'],
                    ['label' => 'Contrats',          'route' => 'app_contracts',   'icon' => 'file-signature'],
                    ['label' => 'Support',           'route' => 'app_support',     'icon' => 'life-buoy'],
                    ['label' => 'Communauté',        'route' => 'app_community',   'icon' => 'message-circle'],
                    ['label' => 'Messagerie',        'route' => 'app_inbox',       'icon' => 'inbox'],
                ],
                'groups' => [],
            ],
        };

        if ($formationRoute !== null) {
            $topnavGrouped['primary'][] = ['label' => 'Formations', 'route' => $formationRoute, 'icon' => 'graduation-cap'];
        }
        if ($learningRoute !== null && $role !== 'ADMIN') {
            $topnavGrouped['primary'][] = ['label' => 'Learning', 'route' => $learningRoute, 'icon' => 'book-open'];
        }

        return [
            'user_role'        => $role,
            'dashboard_route'  => $dashboardRoute,
            'nav_items'        => $navItems,
            'topnav_items'     => $topnavItems,
            'topnav_grouped'   => $topnavGrouped,
        ];
    }

    /** @param list<string> $routeNames */
    private function firstExistingRoute(array $routeNames): ?string
    {
        foreach ($routeNames as $routeName) {
            if ($this->router->getRouteCollection()->get($routeName) !== null) {
                return $routeName;
            }
        }

        return null;
    }
}
