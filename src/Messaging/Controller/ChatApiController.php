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
use App\Service\AI\AiTextService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Cache\CacheItemPoolInterface;

#[Route('/api/chat')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ChatApiController extends AppController
{
    public function __construct(
        private readonly DmConversationRepository $conversationRepository,
        private readonly DmMessageRepository $messageRepository,
        private readonly DmParticipantRepository $participantRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly AiTextService $aiTextService,
        private readonly ?HubInterface $hub,
        private readonly CacheItemPoolInterface $cache,
    ) {
    }

    private function safePublish(Update $update): void
    {
        if ($this->hub === null) {
            return;
        }
        try {
            $this->hub->publish($update);
        } catch (\Throwable) {
        }
    }

    #[Route('/voice', name: 'app_chat_api_voice', methods: ['POST'])]
    public function sendVoice(Request $request): JsonResponse
    {
        $user = $this->getAppUser();
        $conversationId = (int) $request->request->get('conversation_id', 0);
        /** @var UploadedFile|null $voiceFile */
        $voiceFile = $request->files->get('voice');

        if (!$conversationId || !$voiceFile) {
            return $this->json(['error' => 'Missing parameters.'], 400);
        }
        if ($voiceFile->getSize() > 10 * 1024 * 1024) {
            return $this->json(['error' => 'Voice file too large (max 10 MB).'], 400);
        }

        $conversation = $this->findConversation($conversationId, $user);
        if (!$conversation) {
            return $this->json(['error' => 'Access denied.'], 403);
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/voice';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $filename = 'voice-' . $user->getId() . '-' . time() . '.' . ($voiceFile->guessExtension() ?: 'webm');
        $voiceFile->move($uploadDir, $filename);
        $voiceUrl = '/uploads/voice/' . $filename;

        $message = (new DmMessage())
            ->setConversation($conversation)
            ->setSender($user)
            ->setBody('Voice message')
            ->setMessageType(DmMessage::TYPE_VOICE)
            ->setVoiceUrl($voiceUrl);
        $this->persistAndPublish($conversation, $message, $user);

        return $this->json($this->serializeMessage($message, $user), 201);
    }

    #[Route('/image', name: 'app_chat_api_image', methods: ['POST'])]
    public function sendImage(Request $request): JsonResponse
    {
        $user = $this->getAppUser();
        $conversationId = (int) $request->request->get('conversation_id', 0);
        /** @var UploadedFile|null $imageFile */
        $imageFile = $request->files->get('image');

        if (!$conversationId || !$imageFile) {
            return $this->json(['error' => 'Missing parameters.'], 400);
        }
        if ($imageFile->getSize() > 5 * 1024 * 1024) {
            return $this->json(['error' => 'Image too large (max 5 MB).'], 400);
        }
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($imageFile->getMimeType(), $allowed, true)) {
            return $this->json(['error' => 'Unsupported format (JPEG, PNG, GIF, WebP).'], 400);
        }

        $conversation = $this->findConversation($conversationId, $user);
        if (!$conversation) {
            return $this->json(['error' => 'Access denied.'], 403);
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/chat_images';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $filename = 'img-' . $user->getId() . '-' . time() . '.' . ($imageFile->guessExtension() ?: 'jpg');
        $imageFile->move($uploadDir, $filename);
        $imageUrl = '/uploads/chat_images/' . $filename;

        $message = (new DmMessage())
            ->setConversation($conversation)
            ->setSender($user)
            ->setBody('Photo')
            ->setMessageType(DmMessage::TYPE_IMAGE)
            ->setImageUrl($imageUrl);
        $this->persistAndPublish($conversation, $message, $user);

        return $this->json($this->serializeMessage($message, $user), 201);
    }

    #[Route('/typing', name: 'app_chat_api_typing', methods: ['POST'])]
    public function typing(Request $request): JsonResponse
    {
        $user = $this->getAppUser();
        $data = json_decode($request->getContent(), true) ?: [];
        $conversationId = (int) ($data['conversation_id'] ?? 0);

        $conversation = $this->findConversation($conversationId, $user);
        if (!$conversation) {
            return $this->json(['error' => 'Access denied.'], 403);
        }

        $this->safePublish(new Update(
            'chat/conversation/' . $conversationId . '/typing',
            json_encode(['type' => 'typing', 'user_id' => $user->getId(), 'user_name' => $user->getDisplayName(), 'timestamp' => time()])
        ));

        return $this->json(['ok' => true]);
    }

    #[Route('/read/{conversationId}', name: 'app_chat_api_read', methods: ['POST'], requirements: ['conversationId' => '\d+'])]
    public function markAsRead(int $conversationId): JsonResponse
    {
        $user = $this->getAppUser();
        $conversation = $this->findConversation($conversationId, $user);
        if (!$conversation) {
            return $this->json(['error' => 'Access denied.'], 403);
        }

        $qb = $this->entityManager->createQueryBuilder();
        $qb->update(DmMessage::class, 'm')
            ->set('m.isRead', ':true')
            ->set('m.readAt', ':now')
            ->where('m.conversation = :conv')
            ->andWhere('m.sender != :me')
            ->andWhere('m.isRead = :false')
            ->setParameter('conv', $conversation)
            ->setParameter('me', $user)
            ->setParameter('true', true)
            ->setParameter('false', false)
            ->setParameter('now', new \DateTimeImmutable());
        $updated = $qb->getQuery()->execute();

        if ($updated > 0) {
            $this->safePublish(new Update(
                'chat/conversation/' . $conversationId . '/read',
                json_encode(['type' => 'read_receipt', 'reader_id' => $user->getId(), 'read_at' => (new \DateTimeImmutable())->format('H:i')])
            ));
        }

        return $this->json(['marked' => $updated]);
    }

    #[Route('/presence', name: 'app_chat_api_presence', methods: ['POST'])]
    public function presence(Request $request): JsonResponse
    {
        $user = $this->getAppUser();
        $data = json_decode($request->getContent(), true) ?: [];
        $conversationId = (int) ($data['conversation_id'] ?? 0);
        $status = ($data['status'] ?? '') === 'online' ? 'online' : 'offline';

        $conversation = $this->findConversation($conversationId, $user);
        if (!$conversation) {
            return $this->json(['error' => 'Access denied.'], 403);
        }

        $this->safePublish(new Update(
            'chat/conversation/' . $conversationId . '/presence',
            json_encode(['type' => 'presence', 'user_id' => $user->getId(), 'status' => $status])
        ));

        return $this->json(['ok' => true]);
    }

    #[Route('/summarize/{conversationId}', name: 'app_chat_api_summarize', methods: ['POST'], requirements: ['conversationId' => '\d+'])]
    public function summarize(int $conversationId): JsonResponse
    {
        $user = $this->getAppUser();
        $conversation = $this->findConversation($conversationId, $user);
        if (!$conversation) {
            return $this->json(['error' => 'Access denied.'], 403);
        }

        $messages = $this->messageRepository->findForConversation($conversation, 0, 50);
        if (count($messages) < 2) {
            return $this->json(['error' => 'Not enough messages to summarize (min 2).'], 400);
        }

        $formatted = array_map(fn (DmMessage $m) => $m->getSender()->getDisplayName() . ': ' . $m->getBody(), $messages);
        $summary = $this->aiTextService->summarizeMessages($formatted);

        return $this->json(['summary' => $summary ?: 'No summary available.', 'message_count' => count($messages)]);
    }

    #[Route('/correct', name: 'app_chat_api_correct', methods: ['POST'])]
    public function correctText(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?: [];
        $text = trim((string) ($data['text'] ?? ''));
        if ($text === '') {
            return $this->json(['error' => 'No text provided.'], 400);
        }

        $corrected = $this->aiTextService->correctText($text);

        return $this->json(['corrected' => $corrected ?? $text, 'changed' => $corrected !== null && $corrected !== $text]);
    }

    #[Route('/detect-tone', name: 'app_chat_api_detect_tone', methods: ['POST'])]
    public function detectTone(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?: [];
        $text = trim((string) ($data['text'] ?? ''));

        return $this->json(['tone' => $this->aiTextService->detectTone($text)]);
    }

    #[Route('/translate', name: 'app_chat_api_translate', methods: ['POST'])]
    public function translate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?: [];
        $text = trim((string) ($data['text'] ?? ''));
        $lang = trim((string) ($data['lang'] ?? 'English'));
        if ($text === '') {
            return $this->json(['error' => 'No text provided.'], 400);
        }

        $translated = $this->aiTextService->translate($text, $lang);

        return $this->json(['translated' => $translated ?? $text]);
    }

    #[Route('/call/start', name: 'app_chat_api_call_start', methods: ['POST'])]
    public function callStart(Request $request): JsonResponse
    {
        $user = $this->getAppUser();
        $data = json_decode($request->getContent(), true) ?: [];
        $conversationId = (int) ($data['conversation_id'] ?? 0);
        $callType = in_array($data['call_type'] ?? '', ['audio', 'video'], true) ? $data['call_type'] : 'audio';

        $conversation = $this->findConversation($conversationId, $user);
        if (!$conversation) {
            return $this->json(['error' => 'Access denied.'], 403);
        }

        $signal = [
            'type' => 'call_offer',
            'caller_id' => $user->getId(),
            'caller_name' => $user->getDisplayName(),
            'call_type' => $callType,
            'timestamp' => time(),
        ];

        $cacheItem = $this->cache->getItem('call_signal_' . $conversationId);
        $cacheItem->set($signal);
        $cacheItem->expiresAfter(45);
        $this->cache->save($cacheItem);

        $this->safePublish(new Update(
            'chat/conversation/' . $conversationId . '/call',
            json_encode($signal)
        ));

        return $this->json(['ok' => true, 'call_type' => $callType]);
    }

    #[Route('/call/end', name: 'app_chat_api_call_end', methods: ['POST'])]
    public function callEnd(Request $request): JsonResponse
    {
        $user = $this->getAppUser();
        $data = json_decode($request->getContent(), true) ?: [];
        $conversationId = (int) ($data['conversation_id'] ?? 0);

        $conversation = $this->findConversation($conversationId, $user);
        if (!$conversation) {
            return $this->json(['error' => 'Access denied.'], 403);
        }

        $this->cache->deleteItem('call_signal_' . $conversationId);

        $this->safePublish(new Update(
            'chat/conversation/' . $conversationId . '/call',
            json_encode([
                'type' => 'call_end',
                'user_id' => $user->getId(),
                'timestamp' => time(),
            ])
        ));

        return $this->json(['ok' => true]);
    }

    #[Route('/call/check/{conversationId}', name: 'app_chat_api_call_check', methods: ['GET'], requirements: ['conversationId' => '\d+'])]
    public function callCheck(int $conversationId): JsonResponse
    {
        $user = $this->getAppUser();
        $conversation = $this->findConversation($conversationId, $user);
        if (!$conversation) {
            return $this->json(['active' => false]);
        }

        $cacheItem = $this->cache->getItem('call_signal_' . $conversationId);
        if (!$cacheItem->isHit()) {
            return $this->json(['active' => false]);
        }

        $signal = $cacheItem->get();
        if (!is_array($signal) || ($signal['caller_id'] ?? 0) === $user->getId()) {
            return $this->json(['active' => false]);
        }

        return $this->json(['active' => true, 'signal' => $signal]);
    }

    #[Route('/call/signal', name: 'app_chat_api_call_signal_post', methods: ['POST'])]
    public function callSignalPost(Request $request): JsonResponse
    {
        $user = $this->getAppUser();
        $data = json_decode($request->getContent(), true) ?: [];
        $conversationId = (int) ($data['conversation_id'] ?? 0);
        $signalType = (string) ($data['signal_type'] ?? '');

        $conversation = $this->findConversation($conversationId, $user);
        if (!$conversation) {
            return $this->json(['error' => 'Access denied.'], 403);
        }

        $userId = $user->getId();

        if ($signalType === 'offer') {
            $item = $this->cache->getItem('call_sdp_offer_' . $conversationId);
            $item->set(['sdp' => $data['sdp'] ?? '', 'from' => $userId]);
            $item->expiresAfter(60);
            $this->cache->save($item);
        } elseif ($signalType === 'answer') {
            $item = $this->cache->getItem('call_sdp_answer_' . $conversationId);
            $item->set(['sdp' => $data['sdp'] ?? '', 'from' => $userId]);
            $item->expiresAfter(60);
            $this->cache->save($item);
        } elseif ($signalType === 'ice') {
            $key = 'call_ice_' . $conversationId . '_' . $userId;
            $item = $this->cache->getItem($key);
            $existing = $item->isHit() ? ($item->get() ?: []) : [];
            $existing[] = $data['candidate'] ?? [];
            $item->set($existing);
            $item->expiresAfter(60);
            $this->cache->save($item);
        }

        return $this->json(['ok' => true]);
    }

    #[Route('/call/signal/{conversationId}', name: 'app_chat_api_call_signal_get', methods: ['GET'], requirements: ['conversationId' => '\d+'])]
    public function callSignalGet(int $conversationId, Request $request): JsonResponse
    {
        $user = $this->getAppUser();
        $conversation = $this->findConversation($conversationId, $user);
        if (!$conversation) {
            return $this->json(['error' => 'Access denied.'], 403);
        }

        $userId = $user->getId();
        $result = [];

        $need = $request->query->getString('need', '');

        if ($need === 'offer' || $need === '') {
            $item = $this->cache->getItem('call_sdp_offer_' . $conversationId);
            if ($item->isHit()) {
                $d = $item->get();
                if (is_array($d) && ($d['from'] ?? 0) !== $userId) {
                    $result['offer'] = $d['sdp'];
                }
            }
        }

        if ($need === 'answer' || $need === '') {
            $item = $this->cache->getItem('call_sdp_answer_' . $conversationId);
            if ($item->isHit()) {
                $d = $item->get();
                if (is_array($d) && ($d['from'] ?? 0) !== $userId) {
                    $result['answer'] = $d['sdp'];
                }
            }
        }

        $participants = $conversation->getParticipants();
        $iceCandidates = [];
        foreach ($participants as $p) {
            $pId = $p->getUser()->getId();
            if ($pId === $userId) {
                continue;
            }
            $key = 'call_ice_' . $conversationId . '_' . $pId;
            $item = $this->cache->getItem($key);
            if ($item->isHit() && is_array($item->get())) {
                $iceCandidates = array_merge($iceCandidates, $item->get());
            }
        }
        $result['ice_candidates'] = $iceCandidates;

        return $this->json($result);
    }

    private function findConversation(int $id, User $user): ?DmConversation
    {
        $conv = $this->conversationRepository->find($id);
        if (!$conv || $this->participantRepository->findOneForUserAndConversation($user, $conv) === null) {
            return null;
        }

        return $conv;
    }

    private function persistAndPublish(DmConversation $conversation, DmMessage $message, User $sender): void
    {
        $conversation->addMessage($message);
        $conversation->markMessageActivity($message->getCreatedAt());
        foreach ($conversation->getParticipants() as $participant) {
            if ($participant->getUser()->getId() !== $sender->getId()) {
                $participant->incrementUnread();
            } else {
                $participant->markRead();
            }
        }
        $this->entityManager->persist($message);
        $this->entityManager->flush();

        $this->safePublish(new Update(
            'chat/conversation/' . $conversation->getId() . '/messages',
            json_encode(['type' => 'new_message', 'message' => $this->serializeMessage($message, $sender)])
        ));
    }

    /** @return array<string, mixed> */
    private function serializeMessage(DmMessage $message, User $viewer): array
    {
        return [
            'id' => $message->getId(),
            'body' => $message->getBody(),
            'sender_id' => $message->getSender()->getId(),
            'sender_name' => $message->getSender()->getDisplayName(),
            'message_type' => $message->getMessageType(),
            'voice_url' => $message->getVoiceUrl(),
            'image_url' => $message->getImageUrl(),
            'is_mine' => $message->getSender()->getId() === $viewer->getId(),
            'is_read' => $message->isRead(),
            'read_at' => $message->getReadAt()?->format('H:i'),
            'created_at' => $message->getCreatedAt()->format('M d, H:i'),
        ];
    }
}
