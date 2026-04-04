<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class UserListController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/database/users', name: 'app_database_users', methods: ['GET'])]
    public function index(UserRepository $users, EntityManagerInterface $em): Response
    {
        try {
            $em->getConnection()->connect();
        } catch (\Throwable $e) {
            $this->logger->error('Database connection failed', ['exception' => $e]);

            return $this->render('database/users_error.html.twig', [
                'message' => 'Could not connect to the database. Check that MySQL is running and DATABASE_URL is correct.',
            ], new Response('', Response::HTTP_SERVICE_UNAVAILABLE));
        }

        try {
            $userRows = $users->findAllForListing(200);
        } catch (DbalException $e) {
            $this->logger->error('Failed to load users', ['exception' => $e]);

            return $this->render('database/users_error.html.twig', [
                'message' => 'A database error occurred while loading users.',
            ], new Response('', Response::HTTP_INTERNAL_SERVER_ERROR));
        }

        return $this->render('admin/users/index.html.twig', [
            'users' => $userRows,
        ]);
    }
}
