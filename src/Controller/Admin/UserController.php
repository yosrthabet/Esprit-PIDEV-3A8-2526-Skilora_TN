<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class UserController extends AppController
{
    private const ROLES = ['USER', 'EMPLOYER', 'TRAINER', 'ADMIN'];

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/users', name: 'app_admin_user_index', methods: ['GET'])]
    #[Route('/admin/user', name: 'app_admin_user_index_legacy', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $query = trim($request->query->getString('q')) ?: null;
        $role = $request->query->getString('role', 'all');
        $status = $request->query->getString('status', 'all');
        $page = max(1, $request->query->getInt('page', 1));
        $perPage = 20;
        $total = $this->userRepository->countForAdmin($query, $role, $status);

        return $this->render('admin/user/index.html.twig', [
            'users' => $this->userRepository->findForAdmin($query, $role, $status, $perPage, ($page - 1) * $perPage),
            'q' => $query,
            'role' => $role,
            'status' => $status,
            'roles' => self::ROLES,
            'page' => $page,
            'page_count' => max(1, (int) ceil($total / $perPage)),
            'total' => $total,
            'stats' => [
                'total' => $this->userRepository->countAll(),
                'active' => $this->userRepository->countActiveAccounts(),
                'verified' => $this->userRepository->countVerifiedAccounts(),
                'employers' => $this->userRepository->countByRole('EMPLOYER'),
                'trainers' => $this->userRepository->countByRole('TRAINER'),
            ],
        ]);
    }

    #[Route('/admin/users/{id}/edit', name: 'app_admin_user_edit', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
    #[Route('/admin/user/{id}/edit', name: 'app_admin_user_edit_legacy', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
    public function edit(Request $request, User $managedUser): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('admin_user_edit_' . $managedUser->getId(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }

            $role = strtoupper($request->request->getString('role'));
            if (!in_array($role, self::ROLES, true)) {
                $role = 'USER';
            }

            $managedUser
                ->setUsername(trim($request->request->getString('username')) ?: (string) $managedUser->getUsername())
                ->setEmail(trim($request->request->getString('email')) ?: null)
                ->setFullName(trim($request->request->getString('full_name')) ?: null)
                ->setRole($role)
                ->setVerified($request->request->getBoolean('verified'));

            if ($managedUser->getId() !== $this->getAppUser()->getId()) {
                $managedUser->setActive($request->request->getBoolean('active'));
            }

            $this->entityManager->flush();
            $this->addFlash('success', 'User updated.');

            return $this->redirectToRoute('app_admin_user_index');
        }

        return $this->render('admin/user/edit.html.twig', [
            'managed_user' => $managedUser,
            'roles' => self::ROLES,
        ]);
    }

    #[Route('/admin/users/{id}/toggle-active', name: 'app_admin_user_toggle_active', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function toggleActive(Request $request, User $managedUser): Response
    {
        if (!$this->isCsrfTokenValid('admin_user_toggle_' . $managedUser->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        if ($managedUser->getId() === $this->getAppUser()->getId()) {
            $this->addFlash('error', 'You cannot deactivate your own account.');

            return $this->redirectToRoute('app_admin_user_index');
        }

        $managedUser->setActive(!$managedUser->isActive());
        $this->entityManager->flush();

        return $this->redirectToRoute('app_admin_user_index');
    }
}
