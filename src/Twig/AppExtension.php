<?php

namespace App\Twig;

use App\Entity\User;
use App\Enum\WorkType;
use App\Finance\Repository\WalletRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private readonly Security $security,
        private readonly WalletRepository $walletRepository,
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
            new TwigFunction('wallet_balance', [$this, 'getWalletBalance']),
        ];
    }

    public function getWalletBalance(): string
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return '0.00';
        }
        $wallet = $this->walletRepository->findOneForUser($user);
        return $wallet !== null ? $wallet->getBalance() : '0.00';
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
                ['label' => 'Dashboard',       'route' => 'app_dashboard',          'icon' => 'layout-dashboard'],
                ['label' => 'separator'],
                ['label' => 'Users',           'route' => 'app_admin_user_index',   'icon' => 'users'],
                ['label' => 'Recruitment',     'route' => 'app_admin_recruitment',  'icon' => 'briefcase'],
                ['label' => 'Formations',      'route' => 'app_admin_formations',   'icon' => 'graduation-cap'],
                ['label' => 'Certificates',    'route' => 'app_admin_certificates', 'icon' => 'award'],
                ['label' => 'Finance',         'route' => 'app_admin_finance',      'icon' => 'wallet'],
                ['label' => 'Contracts',       'route' => 'app_contracts',          'icon' => 'file-signature'],
                ['label' => 'separator'],
                ['label' => 'Support',         'route' => 'app_admin_support',      'icon' => 'life-buoy'],
                ['label' => 'Calendar',        'route' => 'app_admin_support_calendar', 'icon' => 'calendar'],
                ['label' => 'Reviews',         'route' => 'app_admin_finance_reviews',  'icon' => 'star'],
                ['label' => 'Community',       'route' => 'app_admin_community',    'icon' => 'message-circle'],
                ['label' => 'separator'],
                ['label' => 'AI Recruitment',  'route' => 'app_recruitment_ml',     'icon' => 'sparkles'],
                ['label' => 'AI Formations',   'route' => 'app_formation_ml',       'icon' => 'sparkles'],
                ['label' => 'Chatbot',         'route' => 'app_chatbot',            'icon' => 'bot'],
            ],
            'EMPLOYER' => [
                ['label' => 'Dashboard',       'route' => 'app_workspace',          'icon' => 'layout-dashboard'],
                ['label' => 'separator'],
                ['label' => 'Post a Job',      'route' => 'app_post_job',           'icon' => 'plus-circle'],
                ['label' => 'My Offers',       'route' => 'app_active_offers',      'icon' => 'briefcase'],
                ['label' => 'Applications',    'route' => 'app_applications',       'icon' => 'inbox'],
                ['label' => 'Interviews',      'route' => 'app_interviews',         'icon' => 'video'],
                ['label' => 'Hire Offers',     'route' => 'app_hire_offers',        'icon' => 'handshake'],
                ['label' => 'separator'],
                ['label' => 'Finance',         'route' => 'app_finance',            'icon' => 'wallet'],
                ['label' => 'Contracts',       'route' => 'app_contracts',          'icon' => 'file-signature'],
                ['label' => 'Invoices',        'route' => 'app_finance_invoices',   'icon' => 'receipt'],
                ['label' => 'Escrow',          'route' => 'app_finance_escrow',     'icon' => 'landmark'],
                ['label' => 'separator'],
                ['label' => 'Community',       'route' => 'app_community',          'icon' => 'message-circle'],
                ['label' => 'Messages',        'route' => 'app_inbox',              'icon' => 'messages-square'],
                ['label' => 'Support',         'route' => 'app_support',            'icon' => 'life-buoy'],
            ],
            'TRAINER' => [
                ['label' => 'Dashboard',       'route' => 'app_trainer_dashboard',  'icon' => 'layout-dashboard'],
                ['label' => 'separator'],
                ['label' => 'My Formations',   'route' => 'app_trainer_formations', 'icon' => 'graduation-cap'],
                ['label' => 'Catalog',         'route' => 'app_formations',         'icon' => 'library'],
                ['label' => 'Learning',        'route' => 'app_learning',           'icon' => 'book-open'],
                ['label' => 'Certificates',    'route' => 'app_certificates',       'icon' => 'award'],
                ['label' => 'separator'],
                ['label' => 'Community',       'route' => 'app_community',          'icon' => 'message-circle'],
                ['label' => 'Network',         'route' => 'app_community_network',  'icon' => 'users'],
                ['label' => 'Messages',        'route' => 'app_inbox',              'icon' => 'messages-square'],
                ['label' => 'Support',         'route' => 'app_support',            'icon' => 'life-buoy'],
            ],
            default => [ // USER / FREELANCER
                ['label' => 'Home',            'route' => 'app_workspace',          'icon' => 'home'],
                ['label' => 'separator'],
                ['label' => 'Find Jobs',       'route' => 'app_jobs',               'icon' => 'search'],
                ['label' => 'Applications',    'route' => 'app_applications',       'icon' => 'file-text'],
                ['label' => 'Hire Offers',     'route' => 'app_hire_offers',        'icon' => 'handshake'],
                ['label' => 'Preferences',     'route' => 'app_candidate_job_preferences', 'icon' => 'sliders-horizontal'],
                ['label' => 'separator'],
                ['label' => 'Finance',         'route' => 'app_finance',            'icon' => 'wallet'],
                ['label' => 'Contracts',       'route' => 'app_contracts',          'icon' => 'file-signature'],
                ['label' => 'Invoices',        'route' => 'app_finance_invoices',   'icon' => 'receipt'],
                ['label' => 'Escrow',          'route' => 'app_finance_escrow',     'icon' => 'landmark'],
                ['label' => 'separator'],
                ['label' => 'Formations',      'route' => 'app_formations',         'icon' => 'graduation-cap'],
                ['label' => 'Learning',        'route' => 'app_learning',           'icon' => 'book-open'],
                ['label' => 'Certificates',    'route' => 'app_certificates',       'icon' => 'award'],
                ['label' => 'separator'],
                ['label' => 'Community',       'route' => 'app_community',          'icon' => 'message-circle'],
                ['label' => 'Network',         'route' => 'app_community_network',  'icon' => 'users'],
                ['label' => 'Messages',        'route' => 'app_inbox',              'icon' => 'inbox'],
                ['label' => 'Support',         'route' => 'app_support',            'icon' => 'life-buoy'],
            ],
        };

        $topnavItems = array_values(array_filter($navItems, fn(array $item) => $item['label'] !== 'separator'));

        $topnavGrouped = match ($role) {
            'ADMIN' => [
                'primary' => [
                    ['label' => 'Dashboard',    'route' => 'app_dashboard',          'icon' => 'layout-dashboard'],
                    ['label' => 'Users',        'route' => 'app_admin_user_index',   'icon' => 'users'],
                    ['label' => 'Recruitment',  'route' => 'app_admin_recruitment',  'icon' => 'briefcase'],
                    ['label' => 'Formations',   'route' => 'app_admin_formations',   'icon' => 'graduation-cap'],
                    ['label' => 'Finance',      'route' => 'app_admin_finance',      'icon' => 'wallet'],
                    ['label' => 'Support',      'route' => 'app_admin_support',      'icon' => 'life-buoy'],
                ],
                'groups' => [
                    [
                        'label' => 'Manage', 'icon' => 'shield', 'children' => [
                            ['label' => 'Certificates', 'route' => 'app_admin_certificates', 'icon' => 'award'],
                            ['label' => 'Reviews',      'route' => 'app_admin_finance_reviews', 'icon' => 'star'],
                            ['label' => 'Calendar',     'route' => 'app_admin_support_calendar', 'icon' => 'calendar'],
                            ['label' => 'Payouts',       'route' => 'app_admin_finance_payouts', 'icon' => 'banknote'],
                            ['label' => 'Exchange Rates','route' => 'app_admin_finance_exchange_rates', 'icon' => 'arrow-left-right'],
                            ['label' => 'Payslips',     'route' => 'app_admin_finance_payslips', 'icon' => 'file-text'],
                            ['label' => 'Community',    'route' => 'app_admin_community', 'icon' => 'message-circle'],
                        ],
                    ],
                    [
                        'label' => 'AI Tools', 'icon' => 'sparkles', 'children' => [
                            ['label' => 'AI Recruitment', 'route' => 'app_recruitment_ml', 'icon' => 'sparkles'],
                            ['label' => 'AI Formations',  'route' => 'app_formation_ml',  'icon' => 'sparkles'],
                            ['label' => 'Chatbot',        'route' => 'app_chatbot',       'icon' => 'bot'],
                        ],
                    ],
                ],
            ],
            'EMPLOYER' => [
                'primary' => [
                    ['label' => 'Dashboard',    'route' => 'app_workspace',          'icon' => 'layout-dashboard'],
                    ['label' => 'My Offers',    'route' => 'app_active_offers',      'icon' => 'briefcase'],
                    ['label' => 'Applications', 'route' => 'app_applications',       'icon' => 'inbox'],
                    ['label' => 'Interviews',   'route' => 'app_interviews',         'icon' => 'video'],
                ],
                'groups' => [
                    [
                        'label' => 'Finance', 'icon' => 'wallet', 'children' => [
                            ['label' => 'Overview',  'route' => 'app_finance',          'icon' => 'bar-chart-3'],
                            ['label' => 'Contracts', 'route' => 'app_contracts',        'icon' => 'file-signature'],
                            ['label' => 'Invoices',  'route' => 'app_finance_invoices', 'icon' => 'receipt'],
                            ['label' => 'Escrow',    'route' => 'app_finance_escrow',   'icon' => 'landmark'],
                            ['label' => 'Payouts',   'route' => 'app_finance_payouts',  'icon' => 'banknote'],
                        ],
                    ],
                    [
                        'label' => 'Social', 'icon' => 'users', 'children' => [
                            ['label' => 'Community', 'route' => 'app_community',          'icon' => 'message-circle'],
                            ['label' => 'Network',   'route' => 'app_community_network',  'icon' => 'users'],
                            ['label' => 'Messages',  'route' => 'app_inbox',              'icon' => 'messages-square'],
                            ['label' => 'Support',   'route' => 'app_support',            'icon' => 'life-buoy'],
                        ],
                    ],
                    [
                        'label' => 'AI Tools', 'icon' => 'sparkles', 'children' => [
                            ['label' => 'AI Recruitment', 'route' => 'app_recruitment_ml', 'icon' => 'sparkles'],
                            ['label' => 'Chatbot',        'route' => 'app_chatbot',       'icon' => 'bot'],
                        ],
                    ],
                ],
            ],
            'TRAINER' => [
                'primary' => [
                    ['label' => 'Dashboard',     'route' => 'app_trainer_dashboard',  'icon' => 'layout-dashboard'],
                    ['label' => 'My Formations', 'route' => 'app_trainer_formations', 'icon' => 'graduation-cap'],
                    ['label' => 'Catalog',       'route' => 'app_formations',         'icon' => 'library'],
                    ['label' => 'Learning',      'route' => 'app_learning',           'icon' => 'book-open'],
                ],
                'groups' => [
                    [
                        'label' => 'Social', 'icon' => 'users', 'children' => [
                            ['label' => 'Community',    'route' => 'app_community',         'icon' => 'message-circle'],
                            ['label' => 'Network',      'route' => 'app_community_network', 'icon' => 'users'],
                            ['label' => 'Messages',     'route' => 'app_inbox',             'icon' => 'messages-square'],
                            ['label' => 'Certificates', 'route' => 'app_certificates',      'icon' => 'award'],
                            ['label' => 'Support',      'route' => 'app_support',           'icon' => 'life-buoy'],
                        ],
                    ],
                    [
                        'label' => 'AI Tools', 'icon' => 'sparkles', 'children' => [
                            ['label' => 'AI Formations', 'route' => 'app_formation_ml', 'icon' => 'sparkles'],
                            ['label' => 'Chatbot',       'route' => 'app_chatbot',      'icon' => 'bot'],
                        ],
                    ],
                ],
            ],
            default => [ // USER / FREELANCER
                'primary' => [
                    ['label' => 'Home',         'route' => 'app_workspace',          'icon' => 'home'],
                    ['label' => 'Find Jobs',    'route' => 'app_jobs',               'icon' => 'search'],
                    ['label' => 'Applications', 'route' => 'app_applications',       'icon' => 'file-text'],
                    ['label' => 'Contracts',    'route' => 'app_contracts',          'icon' => 'file-signature'],
                ],
                'groups' => [
                    [
                        'label' => 'Finance', 'icon' => 'wallet', 'children' => [
                            ['label' => 'Overview',  'route' => 'app_finance',          'icon' => 'bar-chart-3'],
                            ['label' => 'Invoices',  'route' => 'app_finance_invoices', 'icon' => 'receipt'],
                            ['label' => 'Escrow',    'route' => 'app_finance_escrow',   'icon' => 'landmark'],
                            ['label' => 'Analytics', 'route' => 'app_finance_analytics','icon' => 'trending-up'],
                            ['label' => 'Payouts',   'route' => 'app_finance_payouts',  'icon' => 'banknote'],
                        ],
                    ],
                    [
                        'label' => 'Learning', 'icon' => 'graduation-cap', 'children' => [
                            ['label' => 'Formations',   'route' => 'app_formations',   'icon' => 'graduation-cap'],
                            ['label' => 'My Courses',   'route' => 'app_learning',     'icon' => 'book-open'],
                            ['label' => 'Certificates', 'route' => 'app_certificates', 'icon' => 'award'],
                        ],
                    ],
                    [
                        'label' => 'Social', 'icon' => 'users', 'children' => [
                            ['label' => 'Community', 'route' => 'app_community',          'icon' => 'message-circle'],
                            ['label' => 'Network',   'route' => 'app_community_network',  'icon' => 'users'],
                            ['label' => 'Messages',  'route' => 'app_inbox',              'icon' => 'inbox'],
                            ['label' => 'Support',   'route' => 'app_support',            'icon' => 'life-buoy'],
                        ],
                    ],
                    [
                        'label' => 'AI Tools', 'icon' => 'sparkles', 'children' => [
                            ['label' => 'AI Recruitment', 'route' => 'app_recruitment_ml', 'icon' => 'sparkles'],
                            ['label' => 'AI Formations',  'route' => 'app_formation_ml',  'icon' => 'sparkles'],
                            ['label' => 'Chatbot',        'route' => 'app_chatbot',       'icon' => 'bot'],
                        ],
                    ],
                ],
            ],
        };

        return [
            'user_role'        => $role,
            'dashboard_route'  => $dashboardRoute,
            'nav_items'        => $navItems,
            'topnav_items'     => $topnavItems,
            'topnav_grouped'   => $topnavGrouped,
        ];
    }
}
