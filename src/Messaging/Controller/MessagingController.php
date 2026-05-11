<?php

declare(strict_types=1);

namespace App\Messaging\Controller;

use App\Community\Repository\MemberInvitationRepository;
use App\Controller\AppController;
use App\Entity\User;
use App\Messaging\Entity\DmConversation;
use App\Messaging\Entity\DmMessage;
use App\Messaging\Repository\DmConversationRepository;
use App\Messaging\Repository\DmMessageRepository;
use App\Messaging\Repository\DmParticipantRepository;
use App\Messaging\Service\MessagingNotifier;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MessagingController extends AppController
{
    public function __construct(
        private readonly DmConversationRepository $conversationRepository,
        private readonly DmParticipantRepository $participantRepository,
        private readonly DmMessageRepository $messageRepository,
        private readonly UserRepository $userRepository,
        private readonly MemberInvitationRepository $invitationRepository,
        private readonly MessagingNotifier $notifier,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/inbox', name: 'app_inbox', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(): Response
    {
        $user = $this->getAppUser();
        $conversations = $this->conversationRepository->findForUser($user);
        $active = $conversations[0] ?? null;
        if ($active !== null) {
            $this->markRead($user, $active);
        }

        return $this->render('messaging/inbox/index.html.twig', [
            'conversations' => $conversations,
            'active_conversation' => $active,
            'messages' => $active !== null ? $this->messageRepository->findForConversation($active) : [],
            'contacts' => $this->availableContacts($user),
            'can_send' => $active !== null && $this->canMessage($user, $active),
        ]);
    }

    #[Route('/inbox/{id}', name: 'app_inbox_conversation', methods: ['GET'], requirements: ['id' => '\\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function conversation(DmConversation $conversation): Response
    {
        $user = $this->getAppUser();
        $this->assertParticipant($user, $conversation);
        $this->markRead($user, $conversation);

        return $this->render('messaging/inbox/index.html.twig', [
            'conversations' => $this->conversationRepository->findForUser($user),
            'active_conversation' => $conversation,
            'messages' => $this->messageRepository->findForConversation($conversation),
            'contacts' => $this->availableContacts($user),
            'can_send' => $this->canMessage($user, $conversation),
        ]);
    }

    #[Route('/inbox/start/{userId}', name: 'app_inbox_start', methods: ['POST'], requirements: ['userId' => '\\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function start(Request $request, int $userId): Response
    {
        $user = $this->getAppUser();
        if (!$this->isCsrfTokenValid('inbox_start_' . $userId, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $recipient = $this->userRepository->find($userId);
        if (!$recipient instanceof User || $recipient->getId() === $user->getId() || !$recipient->isActive() || $recipient->isAdmin()) {
            throw $this->createNotFoundException('Recipient not found.');
        }
        if (!$this->invitationRepository->areFriends($user, $recipient)) {
            throw $this->createAccessDeniedException('Direct messages are limited to accepted network connections.');
        }

        $conversation = $this->conversationRepository->findOneDirectConversation($user, $recipient);
        if ($conversation === null) {
            $conversation = new DmConversation();
            $conversation->addParticipant($user);
            $conversation->addParticipant($recipient);
            $this->entityManager->persist($conversation);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_inbox_conversation', ['id' => $conversation->getId()]);
    }

    #[Route('/api/inbox/{id}/messages', name: 'app_inbox_messages', methods: ['GET'], requirements: ['id' => '\\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function messages(Request $request, DmConversation $conversation): JsonResponse
    {
        $user = $this->getAppUser();
        $this->assertParticipant($user, $conversation);
        $this->markRead($user, $conversation);

        return new JsonResponse([
            'messages' => array_map(fn (DmMessage $message) => $this->serializeMessage($message, $user), $this->messageRepository->findForConversation($conversation, $request->query->getInt('after'))),
        ]);
    }

    #[Route('/api/inbox/{id}/send', name: 'app_inbox_send', methods: ['POST'], requirements: ['id' => '\\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function send(Request $request, DmConversation $conversation): JsonResponse
    {
        $user = $this->getAppUser();
        $this->assertParticipant($user, $conversation);
        if (!$this->canMessage($user, $conversation)) {
            throw $this->createAccessDeniedException('Direct messages are limited to accepted network connections.');
        }
        if (!$this->isCsrfTokenValid('inbox_send_' . $conversation->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $body = trim($request->request->getString('body'));
        if ($body === '') {
            return new JsonResponse(['error' => 'Message body is required.'], 422);
        }

        $message = (new DmMessage())->setConversation($conversation)->setSender($user)->setBody($body);
        $conversation->addMessage($message);
        $conversation->markMessageActivity($message->getCreatedAt());
        foreach ($conversation->getParticipants() as $participant) {
            if ($participant->getUser()->getId() !== $user->getId()) {
                $participant->incrementUnread();
                $this->notifier->notifyRecipient($conversation, $user, $participant->getUser(), $body);
            } else {
                $participant->markRead();
            }
        }
        $this->entityManager->persist($message);
        $this->entityManager->flush();

        return new JsonResponse(['message' => $this->serializeMessage($message, $user)], 201);
    }

    #[Route('/api/inbox/messages/{id}/edit', name: 'app_inbox_message_edit', methods: ['POST'], requirements: ['id' => '\\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function editMessage(Request $request, DmMessage $message): JsonResponse
    {
        $user = $this->getAppUser();
        if ($message->getSender()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Not your message.'], 403);
        }
        if (!$this->isCsrfTokenValid('inbox_msg_edit_' . $message->getId(), $request->request->getString('_token'))) {
            return new JsonResponse(['error' => 'Invalid token.'], 403);
        }
        $body = trim($request->request->getString('body'));
        if ($body === '') {
            return new JsonResponse(['error' => 'Body is required.'], 422);
        }
        $message->setBody($body);
        $this->entityManager->flush();

        return new JsonResponse(['message' => $this->serializeMessage($message, $user)]);
    }

    #[Route('/api/inbox/messages/{id}/delete', name: 'app_inbox_message_delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function deleteMessage(Request $request, DmMessage $message): JsonResponse
    {
        $user = $this->getAppUser();
        if ($message->getSender()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Not your message.'], 403);
        }
        if (!$this->isCsrfTokenValid('inbox_msg_delete_' . $message->getId(), $request->request->getString('_token'))) {
            return new JsonResponse(['error' => 'Invalid token.'], 403);
        }
        $this->entityManager->remove($message);
        $this->entityManager->flush();

        return new JsonResponse(['deleted' => true]);
    }

    #[Route('/api/inbox/unread-count', name: 'app_inbox_unread_count', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function unreadCount(): JsonResponse
    {
        return new JsonResponse(['count' => $this->participantRepository->countUnreadForUser($this->getAppUser())]);
    }

    private function assertParticipant(User $user, DmConversation $conversation): void
    {
        if ($this->participantRepository->findOneForUserAndConversation($user, $conversation) === null) {
            throw $this->createAccessDeniedException('You are not a participant in this conversation.');
        }
    }

    private function markRead(User $user, DmConversation $conversation): void
    {
        $participant = $this->participantRepository->findOneForUserAndConversation($user, $conversation);
        if ($participant !== null && $participant->getUnreadCount() > 0) {
            $participant->markRead();
            $this->entityManager->flush();
        }
    }

    /** @return list<User> */
    private function availableContacts(User $user): array
    {
        return $this->invitationRepository->findFriendsFor($user);
    }

    private function canMessage(User $user, DmConversation $conversation): bool
    {
        $other = $conversation->otherParticipant($user);

        return $other instanceof User && $this->invitationRepository->areFriends($user, $other);
    }

    /** @return array<string, mixed> */
    private function serializeMessage(DmMessage $message, User $viewer): array
    {
        return [
            'id' => $message->getId(),
            'body' => $message->getBody(),
            'sender' => $message->getSender()->getDisplayName(),
            'sender_id' => $message->getSender()->getId(),
            'mine' => $message->getSender()->getId() === $viewer->getId(),
            'message_type' => $message->getMessageType(),
            'voice_url' => $message->getVoiceUrl(),
            'image_url' => $message->getImageUrl(),
            'is_read' => $message->isRead(),
            'read_at' => $message->getReadAt()?->format('H:i'),
            'created_at' => $message->getCreatedAt()->format('M d, H:i'),
        ];
    }
}
