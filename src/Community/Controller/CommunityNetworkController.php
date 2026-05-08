<?php

declare(strict_types=1);

namespace App\Community\Controller;

use App\Community\Entity\MemberInvitation;
use App\Community\MemberInvitationStatus;
use App\Community\Repository\MemberInvitationRepository;
use App\Controller\AppController;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CommunityNetworkController extends AppController
{
    public function __construct(
        private readonly MemberInvitationRepository $invitationRepository,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/community/network', name: 'app_community_network', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(): Response
    {
        $user = $this->getNetworkUser();

        return $this->render('community/network/index.html.twig', [
            'friends' => $this->invitationRepository->findFriendsFor($user),
            'received_invitations' => $this->invitationRepository->findReceivedBy($user),
            'sent_invitations' => $this->invitationRepository->findSentBy($user),
            'suggestions' => $this->invitationRepository->findAvailableInvitees($user),
        ]);
    }

    #[Route('/community/network/invitations/{userId}/send', name: 'app_community_invitation_send', methods: ['POST'], requirements: ['userId' => '\\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function send(Request $request, int $userId): Response
    {
        $user = $this->getNetworkUser();
        if (!$this->isCsrfTokenValid('community_invitation_send_' . $userId, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $invitee = $this->userRepository->find($userId);
        if (!$invitee instanceof User || !$invitee->isActive() || $invitee->isAdmin()) {
            throw $this->createNotFoundException('Member not found.');
        }
        if ($invitee->getId() === $user->getId()) {
            $this->addFlash('error', 'You cannot invite yourself.');

            return $this->redirectToRoute('app_community_network');
        }
        if ($this->invitationRepository->areFriends($user, $invitee)) {
            $this->addFlash('info', 'You are already connected with this member.');

            return $this->redirectToRoute('app_community_network');
        }
        if ($this->invitationRepository->findPendingBetween($user, $invitee) !== null) {
            $this->addFlash('warning', 'A pending invitation already exists with this member.');

            return $this->redirectToRoute('app_community_network');
        }

        $invitation = (new MemberInvitation())
            ->setInviter($user)
            ->setInvitee($invitee)
            ->setNote($request->request->getString('note'));
        $this->entityManager->persist($invitation);
        $this->entityManager->flush();

        $this->addFlash('success', 'Invitation sent.');

        return $this->redirectToRoute('app_community_network');
    }

    #[Route('/community/network/invitations/{id}/accept', name: 'app_community_invitation_accept', methods: ['POST'], requirements: ['id' => '\\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function accept(Request $request, MemberInvitation $invitation): Response
    {
        return $this->respondToInvitation($request, $invitation, MemberInvitationStatus::ACCEPTED, 'accept');
    }

    #[Route('/community/network/invitations/{id}/decline', name: 'app_community_invitation_decline', methods: ['POST'], requirements: ['id' => '\\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function decline(Request $request, MemberInvitation $invitation): Response
    {
        return $this->respondToInvitation($request, $invitation, MemberInvitationStatus::DECLINED, 'decline');
    }

    #[Route('/community/network/invitations/{id}/cancel', name: 'app_community_invitation_cancel', methods: ['POST'], requirements: ['id' => '\\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function cancel(Request $request, MemberInvitation $invitation): Response
    {
        $user = $this->getNetworkUser();
        if ($invitation->getInviter()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Only the inviter can cancel this invitation.');
        }
        if (!$this->isCsrfTokenValid('community_invitation_cancel_' . $invitation->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        if (!$invitation->isPending()) {
            $this->addFlash('warning', 'Only pending invitations can be cancelled.');

            return $this->redirectToRoute('app_community_network');
        }

        $invitation->setStatus(MemberInvitationStatus::CANCELLED)->recordResponse();
        $this->entityManager->flush();
        $this->addFlash('success', 'Invitation cancelled.');

        return $this->redirectToRoute('app_community_network');
    }

    private function respondToInvitation(Request $request, MemberInvitation $invitation, MemberInvitationStatus $status, string $action): Response
    {
        $user = $this->getNetworkUser();
        if ($invitation->getInvitee()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Only the invitee can respond to this invitation.');
        }
        if (!$this->isCsrfTokenValid('community_invitation_' . $action . '_' . $invitation->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        if (!$invitation->isPending()) {
            $this->addFlash('warning', 'This invitation is no longer pending.');

            return $this->redirectToRoute('app_community_network');
        }

        $invitation->setStatus($status)->recordResponse();
        $this->entityManager->flush();
        $this->addFlash('success', $status === MemberInvitationStatus::ACCEPTED ? 'Invitation accepted.' : 'Invitation declined.');

        return $this->redirectToRoute('app_community_network');
    }

    private function getNetworkUser(): User
    {
        $user = $this->getAppUser();
        if ($user->isAdmin()) {
            throw $this->createAccessDeniedException('Admins do not participate in the member network.');
        }

        return $user;
    }
}
