<?php

namespace App\Service\User;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

/**
 * AI Profile Intelligence Service — real-time streaming analysis.
 *
 * Streams an SSE-compatible OpenAI response analyzing the full user profile.
 * Returns: roast + rewrite + archetype + competitive edge summary.
 *
 * @phpstan-type ProfileData array{
 *   name: string,
 *   headline: string,
 *   bio: string,
 *   location: string,
 *   skills: list<array{name: string, level: string, years: int}>,
 *   experiences: list<array{company: string, position: string, description: string, current: bool}>,
 *   portfolio: list<array{title: string, description: string, technologies: string}>,
 *   website: string,
 * }
 */
class ProfileAiService
{
    private const OPENAI_URL = 'https://api.groq.com/openai/v1/chat/completions';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $profileOpenAiKey,
        private readonly string $profileOpenAiModel,
    ) {
    }

    /**
     * Build the structured prompt from full profile data.
     *
     * @param ProfileData $profileData
     */
    public function buildPrompt(array $profileData): string
    {
        $skillLines = implode(', ', array_map(
            fn($s) => "{$s['name']} ({$s['level']}, {$s['years']}y)",
            $profileData['skills'] ?? []
        ));

        $expLines = implode("\n", array_map(
            fn($e) => "- {$e['position']} @ {$e['company']}" . ($e['current'] ? ' [current]' : '') . ': ' . mb_substr($e['description'] ?? '', 0, 150),
            $profileData['experiences'] ?? []
        ));

        $portfolioLines = implode("\n", array_map(
            fn($p) => "- {$p['title']}: {$p['description']} [stack: {$p['technologies']}]",
            $profileData['portfolio'] ?? []
        ));

        $name     = htmlspecialchars_decode($profileData['name'] ?? 'Unknown');
        $headline = htmlspecialchars_decode($profileData['headline'] ?? '');
        $bio      = htmlspecialchars_decode($profileData['bio'] ?? '');
        $location = htmlspecialchars_decode($profileData['location'] ?? '');

        return <<<PROMPT
        You are an elite freelance profile strategist with deep knowledge of the North African (Tunisia/MENA) tech freelance market.
        
        Analyze this freelancer profile and return a structured JSON response with EXACTLY these keys:
        
        {
          "roast": "1-2 punchy sentences — brutally honest critique of what's weak, generic, or invisible in this profile. Be specific, not generic.",
          "archetype": "One of: 'The Technical Specialist' | 'The Creative Polymath' | 'The Process Engineer' | 'The Industry Expert' | 'The Growth Catalyst' | 'The Niche Disruptor'. Choose based on skill/exp pattern.",
          "archetype_reason": "1 sentence explaining why this archetype fits.",
          "rewritten_headline": "A punchy, specific new headline (max 12 words) that replaces generic phrases.",
          "rewritten_bio": "A rewritten bio (3-4 sentences) that sounds human, specific, and positions them in the Tunisian/MENA freelance market. No buzzwords.",
          "competitive_edge": "1-2 sentences on their rare skill COMBINATION — what makes their mix genuinely hard to find.",
          "top_3_actions": ["action 1 (specific)", "action 2 (specific)", "action 3 (specific)"]
        }
        
        Return ONLY valid JSON. No markdown fences. No extra text.
        
        PROFILE:
        Name: {$name}
        Location: {$location}
        Headline: {$headline}
        Bio: {$bio}
        Skills: {$skillLines}
        Experience:
        {$expLines}
        Portfolio:
        {$portfolioLines}
        PROMPT;
    }

    /**
     * Stream the AI analysis as SSE chunks.
     * Caller should call ob_flush()/flush() after each yielded chunk.
     *
     * @param ProfileData $profileData
     * @return \Generator<string> SSE-formatted data lines
     */
    public function streamAnalysis(array $profileData): \Generator
    {
        $prompt = $this->buildPrompt($profileData);

        try {
            $response = $this->httpClient->request('POST', self::OPENAI_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->profileOpenAiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'       => $this->profileOpenAiModel,
                    'messages'    => [
                        ['role' => 'system', 'content' => 'You are a freelance profile strategist. Return only valid JSON.'],
                        ['role' => 'user',   'content' => $prompt],
                    ],
                    'stream'      => true,
                    'temperature' => 0.8,
                    'max_tokens'  => 900,
                ],
                'timeout' => 60,
                'buffer'  => false,
            ]);

            $buffer = '';
            foreach ($this->httpClient->stream($response) as $chunk) {
                $content = $chunk->getContent();
                $buffer .= $content;

                // Process complete SSE lines from buffer
                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line   = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 1);

                    if (!str_starts_with($line, 'data: ')) {
                        continue;
                    }

                    $data = substr($line, 6);

                    if ($data === '[DONE]') {
                        yield "data: [DONE]\n\n";
                        return;
                    }

                    $decoded = json_decode($data, true);
                    $token = null;
                    if (is_array($decoded)) {
                        $choices = $decoded['choices'] ?? null;
                        $firstChoice = is_array($choices) ? ($choices[0] ?? null) : null;
                        $delta = is_array($firstChoice) ? ($firstChoice['delta'] ?? null) : null;
                        $content = is_array($delta) ? ($delta['content'] ?? null) : null;
                        $token = is_string($content) ? $content : null;
                    }

                    if ($token !== null) {
                        yield 'data: ' . json_encode(['token' => $token]) . "\n\n";
                    }
                }
            }

        } catch (\Throwable $e) {
            $this->logger->error('ProfileAI stream error', ['error' => $e->getMessage()]);
            yield 'data: ' . json_encode(['error' => 'AI analysis failed. Please try again.']) . "\n\n";
            yield "data: [DONE]\n\n";
        }
    }

    /**
     * Non-streaming fallback — returns parsed JSON or null.
     */
    /**
     * @param ProfileData $profileData
     * @return array<string, mixed>|null
     */
    public function analyze(array $profileData): ?array
    {
        $prompt = $this->buildPrompt($profileData);

        try {
            $response = $this->httpClient->request('POST', self::OPENAI_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->profileOpenAiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'       => $this->profileOpenAiModel,
                    'messages'    => [
                        ['role' => 'system', 'content' => 'You are a freelance profile strategist. Return only valid JSON.'],
                        ['role' => 'user',   'content' => $prompt],
                    ],
                    'temperature' => 0.8,
                    'max_tokens'  => 900,
                ],
                'timeout' => 30,
            ]);

            $data    = $response->toArray();
            $content = $data['choices'][0]['message']['content'] ?? '';
            $parsed  = json_decode(trim($content), true);

            if (!is_array($parsed)) {
                return null;
            }

            $result = [];
            foreach ($parsed as $key => $value) {
                if (!is_string($key)) {
                    return null;
                }

                $result[$key] = $value;
            }

            return $result;

        } catch (\Throwable $e) {
            $this->logger->error('ProfileAI analyze error', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
