<?php

declare(strict_types=1);

namespace App\Recruitment\Service;

final class ApplicationQualityScreeningService
{
    public function analyze(?string $coverLetter, ?string $cvText): ApplicationQualityScreeningResult
    {
        $risk = 0;
        $reasons = [];

        $letter = $this->normalize($coverLetter);
        $cv = $this->normalize($cvText);

        if ($letter === null && $cv === null) {
            return new ApplicationQualityScreeningResult(0, false, []);
        }

        $combined = trim(($letter ?? '')."\n".($cv ?? ''));
        if ($combined === '') {
            return new ApplicationQualityScreeningResult(0, false, []);
        }

        $tokenCount = $this->tokenCount($combined);
        $uniqueRatio = $this->uniqueWordRatio($combined);
        if ($tokenCount >= 30 && $uniqueRatio < 0.26) {
            $risk += 40;
            $reasons[] = 'Highly repetitive text detected (possible copy-paste).';
        }

        $dupLineRatio = $this->duplicateLineRatio($combined);
        if ($dupLineRatio > 0.30) {
            $risk += 30;
            $reasons[] = 'Heavily duplicated lines.';
        }

        $uppercaseRatio = $this->uppercaseRatio($combined);
        if ($uppercaseRatio > 0.55) {
            $risk += 20;
            $reasons[] = 'Excessive uppercase usage.';
        }

        $spamHits = $this->spamKeywordHits($combined);
        if ($spamHits >= 2) {
            $risk += 40;
            $reasons[] = 'Multiple spam/suspicious terms detected.';
        } elseif ($spamHits === 1) {
            $risk += 18;
            $reasons[] = 'Potentially suspicious term found.';
        }

        $urlCount = preg_match_all('/https?:\/\/|www\./i', $combined);
        if (\is_int($urlCount) && $urlCount >= 2) {
            $risk += 20;
            $reasons[] = 'High number of external links.';
        }

        if ($letter !== null && mb_strlen($letter) > 0 && mb_strlen($letter) < 35) {
            $risk += 15;
            $reasons[] = 'Cover letter too short.';
        }

        $risk = min(100, $risk);
        $blocked = $risk >= 45;

        return new ApplicationQualityScreeningResult($risk, $blocked, $reasons);
    }

    private function normalize(?string $text): ?string
    {
        if (!\is_string($text)) {
            return null;
        }
        $t = trim(str_replace("\r\n", "\n", $text));

        return $t !== '' ? $t : null;
    }

    private function tokenCount(string $text): int
    {
        if (!preg_match_all('/[\p{L}\p{N}]{2,}/u', mb_strtolower($text), $m)) {
            return 0;
        }

        return \count($m[0]);
    }

    private function uniqueWordRatio(string $text): float
    {
        if (!preg_match_all('/[\p{L}\p{N}]{2,}/u', mb_strtolower($text), $m)) {
            return 1.0;
        }
        $all = $m[0];
        $total = \count($all);
        if ($total === 0) {
            return 1.0;
        }

        return \count(array_unique($all)) / $total;
    }

    private function duplicateLineRatio(string $text): float
    {
        $rawLines = preg_split('/\R/u', $text) ?: [];
        $lines = [];
        foreach ($rawLines as $line) {
            $t = trim(mb_strtolower($line));
            if ($t !== '') {
                $lines[] = $t;
            }
        }
        $n = \count($lines);
        if ($n < 3) {
            return 0.0;
        }

        $counts = array_count_values($lines);
        $dup = 0;
        foreach ($counts as $c) {
            if ($c > 1) {
                $dup += $c;
            }
        }

        return $dup / $n;
    }

    private function uppercaseRatio(string $text): float
    {
        $letters = preg_replace('/[^\p{L}]+/u', '', $text) ?? '';
        if ($letters === '') {
            return 0.0;
        }
        $upper = mb_strtoupper($letters);
        $uCount = 0;
        $len = mb_strlen($letters);
        for ($i = 0; $i < $len; ++$i) {
            if (mb_substr($letters, $i, 1) === mb_substr($upper, $i, 1)) {
                ++$uCount;
            }
        }

        return $len > 0 ? $uCount / $len : 0.0;
    }

    private function spamKeywordHits(string $text): int
    {
        $t = mb_strtolower($text);
        $keywords = [
            'quick money', 'earn money', 'bitcoin', 'crypto investment',
            'telegram', 'contact me on whatsapp', 'click here',
            'urgent transfer', 'gagner argent', 'casino', 'bet',
        ];

        $hits = 0;
        foreach ($keywords as $k) {
            if (str_contains($t, $k)) {
                ++$hits;
            }
        }

        return $hits;
    }
}
