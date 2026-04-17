<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    #[Route('', name: 'app_admin_user_index')]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        return $this->render('admin/user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    /**
     * Returns the full user list as an HTML fragment (for SPA-like table refresh).
     */
    #[Route('/list', name: 'app_admin_user_list', methods: ['GET'])]
    public function list(UserRepository $userRepository): Response
    {
        return $this->render('admin/user/_table.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    /**
     * Returns user create form as HTML fragment for the dialog.
     */
    #[Route('/new', name: 'app_admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        if ($request->isMethod('POST')) {
            try {
            $user = new User();
            $user->setUsername(trim($request->request->getString('username')));
            $user->setEmail(trim($request->request->getString('email')));
            $user->setFullName(trim($request->request->getString('full_name')));
            $user->setRole($request->request->getString('role', 'USER'));
            $user->setActive($request->request->getBoolean('is_active', false));

            $password = $request->request->getString('password');
            if ($password !== '') {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
            }

            $em->persist($user);
            $em->flush();

            if ($request->headers->has('X-Requested-With')) {
                return new JsonResponse(['success' => true, 'message' => 'User created successfully.']);
            }

            $this->addFlash('success', 'User created successfully.');
            return $this->redirectToRoute('app_admin_user_index');
            } catch (\Exception $e) {
                if ($request->headers->has('X-Requested-With')) {
                    return new JsonResponse(['success' => false, 'message' => 'User creation failed: ' . $e->getMessage()], 500);
                }
                $this->addFlash('error', 'Failed: ' . $e->getMessage());
                return $this->redirectToRoute('app_admin_user_index');
            }
        }

        // AJAX: return form fragment only
        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return $this->render('admin/user/_form.html.twig', ['user' => null]);
        }

        return $this->render('admin/user/new.html.twig');
    }

    /**
     * Returns user edit form as HTML fragment for the dialog, or processes update.
     */
    #[Route('/{id}/edit', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(User $user, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        if ($request->isMethod('POST')) {
            $user->setUsername(trim($request->request->getString('username', $user->getUsername() ?? '')));
            $user->setEmail(trim($request->request->getString('email', $user->getEmail() ?? '')));
            $user->setFullName(trim($request->request->getString('full_name', $user->getFullName() ?? '')));
            $user->setRole($request->request->getString('role', $user->getRole() ?? 'USER'));
            $user->setActive($request->request->getBoolean('is_active', false));

            $password = $request->request->getString('password');
            if ($password !== '') {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
            }

            $em->flush();

            if ($request->headers->has('X-Requested-With')) {
                return new JsonResponse(['success' => true, 'message' => 'User updated successfully.']);
            }

            $this->addFlash('success', 'User updated successfully.');
            return $this->redirectToRoute('app_admin_user_index');
        }

        // AJAX: return form fragment only
        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return $this->render('admin/user/_form.html.twig', ['user' => $user]);
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/toggle-status', name: 'app_admin_user_toggle_status', methods: ['POST'])]
    public function toggleStatus(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('toggle-user-status-'.$user->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $user->setActive(!$user->isActive());
        $em->flush();

        if ($request->headers->has('X-Requested-With')) {
            return new JsonResponse([
                'success' => true,
                'message' => 'User status toggled.',
                'is_active' => $user->isActive(),
            ]);
        }

        $this->addFlash('success', 'User status toggled successfully.');
        return $this->redirectToRoute('app_admin_user_index');
    }

    #[Route('/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function delete(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete-user-'.$user->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $em->remove($user);
        $em->flush();

        if ($request->headers->has('X-Requested-With')) {
            return new JsonResponse(['success' => true, 'message' => 'User deleted.']);
        }

        $this->addFlash('success', 'User deleted successfully.');
        return $this->redirectToRoute('app_admin_user_index');
    }
}

