<?php

declare(strict_types=1);

namespace App\Controller;

use App\Formation\Service\ChatbotService as FormationChatbotService;
use App\Service\AI\SkiloraMlClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ChatbotController extends AppController
{
    public function __construct(
        private readonly SkiloraMlClient $mlClient,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly FormationChatbotService $formationChatbot,
        private readonly string $chatbotApiUrl,
        private readonly string $chatbotApiKey,
        private readonly string $chatbotModel,
    ) {
    }

    #[Route('/chatbot', name: 'app_chatbot', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('chatbot/index.html.twig');
    }

    private const FAQ = [
        'wallet' => 'To top up your wallet, go to Finance → Wallet and use the Stripe payment form. Enter the amount in TND and click "Pay with card". Your balance will be credited once payment is confirmed.',
        'top up' => 'To top up your wallet, go to Finance → Wallet and use the Stripe payment form. Enter the amount in TND and click "Pay with card".',
        'enroll' => 'To enroll in a formation, go to Formations, click the course you want, and click "Enroll". Paid courses deduct from your wallet — make sure you have sufficient balance.',
        'certificate' => 'Certificates are generated automatically once you complete all modules and pass the final quiz. Download or preview your certificate from Learning → Certificates. Each certificate has a unique QR code for verification.',
        'password' => 'To change your password, go to Settings → Security. Enter your current and new password, then click Save.',
        'application' => 'Track your applications from the Applications page in the top nav. You\'ll see each status: pending, reviewed, interview, hired, or rejected.',
        'ticket' => 'To create a support ticket, click "+ New ticket" on the Support page. Fill in subject, description, category, and priority.',
        'interview' => 'Interview details appear in your Applications section — date, time, format (video/phone/in-person), and employer notes.',
        'profile' => 'Update your profile by clicking your avatar → "My Profile". Edit bio, skills, experience, and upload a CV.',
        'job' => 'Find jobs on the Jobs page. Search by keyword, filter by work type, source, and sort by match score, freshness, or salary.',
        'escrow' => 'When an employer publishes a salaried job offer, the amount is held in escrow. The freelancer receives payment when the contract completes.',
        'payment' => 'Skilora uses a wallet system. Top up via Stripe, then use funds for formation enrollment and job escrow. All transactions show in Finance → Wallet.',
        'company' => 'Your company profile is created automatically when you post your first job offer via "Publier une offre".',
        'course' => 'Browse formations on the Formations page. Each course has modules, quizzes, and a certificate upon completion.',
        'formation' => 'Formations are structured online courses. Browse the catalog at Formations, filter by category or level (beginner/intermediate/advanced). Each formation has modules, materials, quizzes, and awards a certificate on completion.',
        'module' => 'Each formation is divided into modules. Complete all modules by marking them done in your Learning space. Progress is tracked automatically.',
        'quiz' => 'Quizzes test your knowledge after completing formation modules. Each quiz has a passing score, time limit, and multiple-choice questions. Pass to earn your certificate.',
        'progress' => 'Track your learning progress in the Learning section. Each enrollment shows your completion percentage. Complete all modules and quizzes to finish a formation.',
        'review' => 'After completing a formation, you can leave a review with a star rating and comment. Reviews help other learners choose the right course.',
        'trainer' => 'Trainers can create formations from Trainer → Formations. Add modules, materials, quizzes, and a certificate signature. Submit for AI review to publish.',
        'recommend' => 'Get AI-powered course recommendations at AI Tools → Formation ML. Enter your skills and interests to discover formations tailored to your learning goals.',
        'verify certificate' => 'Each certificate has a unique verification URL and QR code. Anyone can verify a certificate\'s authenticity by scanning the QR code or visiting the verification link.',
        'learning' => 'Your active enrollments appear in the Learning section. Click any enrollment to see modules, mark progress, take quizzes, and download your certificate once complete.',
    ];

    private const SYSTEM_PROMPT = <<<PROMPT
You are Skilora AI, a smart and friendly assistant for the Skilora platform — a professional freelancing, learning, and recruitment platform.

Platform features you know about:
• Formations: Online courses with modules, quizzes, certificates. Paid formations deduct from wallet. Levels: beginner, intermediate, advanced. Trainers create and manage courses; admins review and publish. Each formation has modules → materials → quizzes. Completing all modules and quizzes issues a verifiable certificate with QR code.
• Formation lifecycle: Trainer creates formation → adds modules/materials → adds certificate signature → submits for AI review → admin publishes. Learners enroll → complete modules → take quizzes → earn certificate.
• Certificates: Auto-issued on 100% completion. Each has a unique verification ID and QR code. Download as PDF. Public verification page at /certificate/verify/{id}.
• Quizzes: Multiple-choice, timed, with passing score threshold. Points per question. Results tracked per user.
• AI Formation tools: Course recommendations based on skills/interests, completion prediction based on progress/quiz scores.
• Recruitment: Job listings from Skilora employers + external feeds (ANETI, Reddit, LinkedIn). AI-powered match scores. CV builder. Interview scheduling.
• Finance: Wallet system (Stripe top-ups), escrow for job offers, contracts, invoices, milestones, payouts. Salary calculator with CNSS + progressive IRPP tax simulation.
• Community: Social feed, blog posts, discussions.
• Support: Ticket system with categories and priorities.

Navigation guide:
- Wallet: Finance → Wallet (or profile dropdown)
- Top up: Finance → Wallet → "Pay with card"
- Formations: top nav "Formations" — browse catalog
- Learning: top nav "Learning" — your enrolled formations + progress
- Certificates: Learning section or Certificates page
- Certificate verify: /certificate/verify/{verificationId}
- AI Recommendations: AI Tools → Formation ML
- Quiz manage: formation detail → Quizzes tab (trainers/admins)
- Trainer dashboard: Trainer → Formations
- Jobs: top nav "Jobs" or search bar
- Applications: top nav "Applications"
- Support: top nav "Support" → "+ New ticket"
- CV Builder: "Build your CV" from job application page
- Salary Calculator: Finance → Salary Calculator
- Profile: click avatar → "My Profile"
- Settings: click avatar → "Settings"

Rules:
- Answer in the same language the user writes in (French or English).
- Be concise (2-3 sentences max).
- Reference specific pages/sections when relevant.
- If you don't know, say so honestly and suggest creating a support ticket.
PROMPT;

    #[Route('/chatbot/ask', name: 'app_chatbot_ask', methods: ['POST'])]
    public function ask(Request $request): JsonResponse
    {
        $message = trim($request->request->getString('message'));
        if ($message === '') {
            return $this->json(['reply' => 'Please type a message.']);
        }

        $context = $request->request->getString('context');
        $contextHint = match ($context) {
            'support' => 'The user is currently on the Support page.',
            'formations' => 'The user is currently on the Formations catalog page — they may be browsing available courses.',
            'learning' => 'The user is currently on their Learning space — they are viewing their enrolled formations and progress.',
            'certificates' => 'The user is currently on the Certificates page — they may need help with certificate download, verification, or QR codes.',
            'quiz' => 'The user is currently taking or managing a quiz in a formation.',
            'recruitment' => 'The user is currently on the Recruitment/Jobs page.',
            'finance' => 'The user is currently on the Finance page.',
            default => '',
        };

        $reply = $this->askLlm($contextHint, $message);

        if ($reply === null) {
            $reply = $this->askMlService($contextHint, $message);
        }

        if ($reply === null) {
            $reply = $this->matchFaq($message);
        }

        return $this->json(['reply' => $reply]);
    }

    private function askLlm(string $contextHint, string $message): ?string
    {
        if ($this->chatbotApiKey === '' || $this->chatbotApiUrl === '') {
            return null;
        }

        try {
            $systemPrompt = self::SYSTEM_PROMPT;
            if ($contextHint !== '') {
                $systemPrompt .= "\n\nContext: " . $contextHint;
            }

            $response = $this->httpClient->request('POST', $this->chatbotApiUrl, [
                'timeout' => 30,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->chatbotApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->chatbotModel ?: 'llama-3.3-70b-versatile',
                    'temperature' => 0.35,
                    'max_tokens' => 500,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $message],
                    ],
                ],
            ]);

            $status = $response->getStatusCode();
            if ($status < 200 || $status >= 300) {
                $this->logger->warning('chatbot.llm.http_error', ['status' => $status]);
                return null;
            }

            $data = $response->toArray();
            $content = $data['choices'][0]['message']['content'] ?? null;

            if (is_string($content) && trim($content) !== '') {
                return trim($content);
            }
        } catch (\Throwable $e) {
            $this->logger->warning('chatbot.llm.error', ['error' => $e->getMessage()]);
        }

        return null;
    }

    private function askMlService(string $contextHint, string $message): ?string
    {
        if (!$this->mlClient->isConfigured()) {
            return null;
        }

        $result = $this->mlClient->smartReply([
            'subject' => 'chatbot',
            'description' => ($contextHint !== '' ? $contextHint . "\n" : '') . 'User question: ' . $message,
        ]);

        $reply = is_string($result['reply'] ?? null) ? $result['reply'] : null;
        if ($reply !== null && !str_contains($reply, 'not configured')) {
            return $reply;
        }

        return null;
    }

    #[Route('/chatbot/formation', name: 'app_chatbot_formation', methods: ['POST'])]
    public function formationChat(Request $request): JsonResponse
    {
        $message = trim($request->request->getString('message'));
        $context = [
            'category' => $request->request->getString('category'),
            'level' => $request->request->getString('level'),
        ];

        $answer = $this->formationChatbot->answer($message, $context);

        return $this->json($answer->toArray());
    }

    private function matchFaq(string $question): string
    {
        $q = mb_strtolower($question);
        $bestAnswer = null;
        $bestScore = 0;

        foreach (self::FAQ as $keyword => $answer) {
            $words = explode(' ', $keyword);
            $score = 0;
            foreach ($words as $word) {
                if (str_contains($q, $word)) {
                    $score++;
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestAnswer = $answer;
            }
        }

        return $bestAnswer ?? 'I can help with questions about formations, recruitment, wallet payments, support tickets, certificates, and more. Could you rephrase your question?';
    }
}
