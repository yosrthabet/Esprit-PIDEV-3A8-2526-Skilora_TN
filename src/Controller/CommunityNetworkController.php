<?php

namespace App\Controller;

use App\Entity\DmConversation;
use App\Entity\DmMessage;
use App\Entity\MemberInvitation;
use App\Entity\User;
use App\Form\DmMessageBodyType;
use App\Form\DmStartConversationType;
use App\Form\MemberInvitationType;
use App\Repository\DmConversationRepository;
use App\Repository\DmMessageRepository;
use App\Repository\MemberInvitationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/community/reseau')]
#[IsGranted('ROLE_USER')]
class CommunityNetworkController extends AbstractController
{
    #[Route('', name: 'app_community_network', methods: ['GET'])]
    public function index(
        Request $request,
        MemberInvitationRepository $invitationRepository,
        DmConversationRepository $conversationRepository,
    ): Response {
        $me = $this->requireUser();

        $invitation = new MemberInvitation();
        $invitation->setInviter($me);
        $inviteForm = $this->createForm(MemberInvitationType::class, $invitation, [
            'current_user' => $me,
            'action' => $this->generateUrl('app_community_invitation_send'),
            'method' => 'POST',
        ]);

        $friends = $invitationRepository->findFriendsFor($me);
        $startDmForm = $this->createForm(DmStartConversationType::class, null, [
            'current_user' => $me,
            'friends' => $friends,
            'action' => $this->generateUrl('app_community_dm_start'),
            'method' => 'POST',
        ]);

        $friendIds = [];
        foreach ($friends as $f) {
            $friendIds[$f->getId()] = true;
        }
        $conversations = array_values(array_filter(
            $conversationRepository->findForUser($me),
            static function (DmConversation $c) use ($me, $friendIds) {
                $other = $c->otherParticipant($me);

                return isset($friendIds[$other->getId()]);
            }
        ));

        $receivedInvitations = $invitationRepository->findReceivedBy($me);
        $sentInvitations = $invitationRepository->findSentBy($me);

        $selectedConversation = null;
        $replyForm = null;
        $conversationId = $request->query->getInt('c', 0);
        if ($conversationId > 0) {
            $c = $conversationRepository->find($conversationId);
            if ($c && $c->involves($me)) {
                $other = $c->otherParticipant($me);
                if ($invitationRepository->areFriends($me, $other)) {
                    $selectedConversation = $c;
                    $reply = new DmMessage();
                    $reply->setConversation($c);
                    $reply->setSender($me);
                    $replyForm = $this->createForm(DmMessageBodyType::class, $reply, [
                        'action' => $this->generateUrl('app_community_dm_send'),
                        'method' => 'POST',
                    ]);
                } else {
                    $this->addFlash('warning', 'La messagerie est réservée à vos amis (invitation acceptée).');
                }
            }
        }

        return $this->render('community/network/index.html.twig', [
            'invite_form' => $inviteForm,
            'start_dm_form' => $startDmForm,
            'conversations' => $conversations,
            'friends' => $friends,
            'received_invitations' => $receivedInvitations,
            'sent_invitations' => $sentInvitations,
            'selected_conversation' => $selectedConversation,
            'reply_form' => $replyForm,
        ]);
    }

    #[Route('/invitation', name: 'app_community_invitation_send', methods: ['POST'])]
    public function sendInvitation(
        Request $request,
        EntityManagerInterface $em,
        MemberInvitationRepository $invitationRepository,
    ): Response {
        $me = $this->requireUser();

        $invitation = new MemberInvitation();
        $invitation->setInviter($me);
        $form = $this->createForm(MemberInvitationType::class, $invitation, ['current_user' => $me]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $invitee = $invitation->getInvitee();
            if ($invitee && $invitationRepository->areFriends($me, $invitee)) {
                $this->addFlash('info', 'Vous êtes déjà ami avec ce membre.');
            } elseif ($invitee && $invitationRepository->findPendingBetween($me, $invitee)) {
                $this->addFlash('warning', 'Une invitation en attente existe déjà pour ce membre.');
            } else {
                $em->persist($invitation);
                $em->flush();
                $this->addFlash('success', 'Invitation envoyée.');
            }
        } else {
            $this->addFlash('error', 'Formulaire d’invitation invalide. Vérifiez les champs.');
        }

        return $this->redirectToRoute('app_community_network');
    }

    #[Route('/invitation/{id}/accept', name: 'app_community_invitation_accept', methods: ['POST'])]
    public function acceptInvitation(Request $request, MemberInvitation $invitation, EntityManagerInterface $em): Response
    {
        $this->assertInvitee($invitation);
        if (!$invitation->isPending()) {
            $this->addFlash('warning', 'Cette invitation n’est plus en attente.');

            return $this->redirectToRoute('app_community_network');
        }
        if (!$this->isCsrfTokenValid('invitation_'.$invitation->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('app_community_network');
        }

        $invitation->setStatus(MemberInvitation::STATUS_ACCEPTED);
        $invitation->setRespondedAt(new \DateTimeImmutable());
        $em->flush();
        $this->addFlash('success', 'Invitation acceptée.');

        return $this->redirectToRoute('app_community_network');
    }

    #[Route('/invitation/{id}/decline', name: 'app_community_invitation_decline', methods: ['POST'])]
    public function declineInvitation(Request $request, MemberInvitation $invitation, EntityManagerInterface $em): Response
    {
        $this->assertInvitee($invitation);
        if (!$invitation->isPending()) {
            $this->addFlash('warning', 'Cette invitation n’est plus en attente.');

            return $this->redirectToRoute('app_community_network');
        }
        if (!$this->isCsrfTokenValid('invitation_'.$invitation->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('app_community_network');
        }

        $invitation->setStatus(MemberInvitation::STATUS_DECLINED);
        $invitation->setRespondedAt(new \DateTimeImmutable());
        $em->flush();
        $this->addFlash('success', 'Invitation refusée.');

        return $this->redirectToRoute('app_community_network');
    }

    #[Route('/invitation/{id}/cancel', name: 'app_community_invitation_cancel', methods: ['POST'])]
    public function cancelInvitation(Request $request, MemberInvitation $invitation, EntityManagerInterface $em): Response
    {
        $this->assertInviter($invitation);
        if (!$invitation->isPending()) {
            $this->addFlash('warning', 'Seules les invitations en attente peuvent être annulées.');

            return $this->redirectToRoute('app_community_network');
        }
        if (!$this->isCsrfTokenValid('invitation_'.$invitation->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('app_community_network');
        }

        $invitation->setStatus(MemberInvitation::STATUS_CANCELLED);
        $invitation->setRespondedAt(new \DateTimeImmutable());
        $em->flush();
        $this->addFlash('success', 'Invitation annulée.');

        return $this->redirectToRoute('app_community_network');
    }

    #[Route('/conversation/start', name: 'app_community_dm_start', methods: ['POST'])]
    public function startConversation(
        Request $request,
        EntityManagerInterface $em,
        DmConversationRepository $conversationRepository,
        MemberInvitationRepository $invitationRepository,
    ): Response {
        $me = $this->requireUser();
        $friends = $invitationRepository->findFriendsFor($me);
        $form = $this->createForm(DmStartConversationType::class, null, [
            'current_user' => $me,
            'friends' => $friends,
        ]);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('error', 'Impossible de démarrer la conversation (contrôle de saisie).');

            return $this->redirectToRoute('app_community_network');
        }

        /** @var User $recipient */
        $recipient = $form->get('recipient')->getData();
        $body = (string) $form->get('body')->getData();

        if (!$invitationRepository->areFriends($me, $recipient)) {
            $this->addFlash('error', 'Vous ne pouvez discuter qu’avec des amis (invitation acceptée).');

            return $this->redirectToRoute('app_community_network');
        }

        $conversation = $conversationRepository->findBetweenUsers($me, $recipient);
        if (!$conversation) {
            $conversation = DmConversation::forUsers($me, $recipient);
            $em->persist($conversation);
            $em->flush();
        }

        $message = new DmMessage();
        $message->setConversation($conversation);
        $message->setSender($me);
        $message->setBody($body);
        $em->persist($message);
        $em->flush();

        $this->addFlash('success', 'Message envoyé.');

        return $this->redirectToRoute('app_community_network', ['c' => $conversation->getId()]);
    }

    #[Route('/message', name: 'app_community_dm_send', methods: ['POST'])]
    public function sendMessage(
        Request $request,
        EntityManagerInterface $em,
        DmConversationRepository $conversationRepository,
        MemberInvitationRepository $invitationRepository,
    ): Response {
        $me = $this->requireUser();
        $conversationId = $request->request->getInt('conversation_id');
        $conversation = $conversationRepository->find($conversationId);
        if (!$conversation || !$conversation->involves($me)) {
            throw $this->createAccessDeniedException();
        }

        $other = $conversation->otherParticipant($me);
        if (!$invitationRepository->areFriends($me, $other)) {
            $this->addFlash('error', 'Vous ne pouvez pas continuer cette conversation (amis uniquement).');

            return $this->redirectToRoute('app_community_network');
        }

        $message = new DmMessage();
        $message->setConversation($conversation);
        $message->setSender($me);
        $form = $this->createForm(DmMessageBodyType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($message);
            $em->flush();
            $this->addFlash('success', 'Message envoyé.');
        } else {
            $this->addFlash('error', 'Message invalide (vide ou trop long).');
        }

        return $this->redirectToRoute('app_community_network', ['c' => $conversation->getId()]);
    }

    #[Route('/message/{id}/edit', name: 'app_community_dm_edit', methods: ['GET', 'POST'])]
    public function editMessage(
        Request $request,
        DmMessage $message,
        EntityManagerInterface $em,
        MemberInvitationRepository $invitationRepository,
    ): Response {
        $me = $this->requireUser();
        if ($message->getSender()->getId() !== $me->getId()) {
            throw $this->createAccessDeniedException();
        }
        if (!$message->getConversation()->involves($me)) {
            throw $this->createAccessDeniedException();
        }
        $convOther = $message->getConversation()->otherParticipant($me);
        if (!$invitationRepository->areFriends($me, $convOther)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(DmMessageBodyType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Message modifié.');

            return $this->redirectToRoute('app_community_network', ['c' => $message->getConversation()->getId()]);
        }

        return $this->render('community/network/edit_message.html.twig', [
            'form' => $form,
            'message' => $message,
        ]);
    }

    #[Route('/message/{id}/delete', name: 'app_community_dm_delete', methods: ['POST'])]
    public function deleteMessage(
        Request $request,
        DmMessage $message,
        EntityManagerInterface $em,
        MemberInvitationRepository $invitationRepository,
    ): Response {
        $me = $this->requireUser();
        if ($message->getSender()->getId() !== $me->getId()) {
            throw $this->createAccessDeniedException();
        }
        $convOther = $message->getConversation()->otherParticipant($me);
        if (!$invitationRepository->areFriends($me, $convOther)) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('delete_dm_'.$message->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('app_community_network', ['c' => $message->getConversation()->getId()]);
        }

        $cid = $message->getConversation()->getId();
        $em->remove($message);
        $em->flush();
        $this->addFlash('success', 'Message supprimé.');

        return $this->redirectToRoute('app_community_network', ['c' => $cid]);
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    private function assertInvitee(MemberInvitation $invitation): void
    {
        $me = $this->requireUser();
        if ($invitation->getInvitee()->getId() !== $me->getId()) {
            throw $this->createAccessDeniedException();
        }
    }

    private function assertInviter(MemberInvitation $invitation): void
    {
        $me = $this->requireUser();
        if ($invitation->getInviter()->getId() !== $me->getId()) {
            throw $this->createAccessDeniedException();
        }
    }
}
