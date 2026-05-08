<?php

declare(strict_types=1);

namespace App\Messaging\Controller;

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
        if (!$recipient instanceof User || $recipient->getId() === $user->getId() || !$recipient->isActive()) {
            throw $this->createNotFoundException('Recipient not found.');
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
        return array_values(array_filter($this->userRepository->findAllOrderedByName(), fn (User $candidate) => $candidate->isActive() && $candidate->getId() !== $user->getId() && !$candidate->isAdmin()));
    }

    /** @return array{id: int|null, body: string, sender: string, mine: bool, created_at: string} */
    private function serializeMessage(DmMessage $message, User $viewer): array
    {
        return [
            'id' => $message->getId(),
            'body' => $message->getBody(),
            'sender' => $message->getSender()->getDisplayName(),
            'mine' => $message->getSender()->getId() === $viewer->getId(),
            'created_at' => $message->getCreatedAt()->format('M d, H:i'),
        ];
    }
}
