<?php

declare(strict_types=1);

namespace App\Recruitment\Service;

use App\Recruitment\Entity\JobOffer;

/**
 * Compare le texte du CV aux exigences de l’offre (mots-clés extraits dynamiquement de l’offre).
 */
final class CvJobMatchScorer
{
    /** @var array<string, true> */
    private const STOP_WORDS = [
        'the' => true, 'a' => true, 'an' => true, 'and' => true, 'or' => true, 'but' => true, 'in' => true, 'on' => true,
        'at' => true, 'to' => true, 'for' => true, 'of' => true, 'with' => true, 'by' => true, 'from' => true, 'as' => true,
        'is' => true, 'are' => true, 'was' => true, 'were' => true, 'be' => true, 'been' => true, 'being' => true,
        'have' => true, 'has' => true, 'had' => true, 'do' => true, 'does' => true, 'did' => true, 'will' => true,
        'would' => true, 'could' => true, 'should' => true, 'may' => true, 'might' => true, 'must' => true, 'can' => true,
        'this' => true, 'that' => true, 'these' => true, 'those' => true, 'we' => true, 'you' => true, 'they' => true,
        'he' => true, 'she' => true, 'it' => true, 'our' => true, 'your' => true, 'their' => true, 'its' => true,
        'not' => true, 'no' => true, 'yes' => true, 'all' => true, 'any' => true, 'some' => true, 'such' => true,
        'more' => true, 'most' => true, 'other' => true, 'than' => true, 'into' => true, 'also' => true, 'just' => true,
        'about' => true, 'over' => true, 'after' => true, 'before' => true, 'between' => true, 'under' => true,
        'le' => true, 'la' => true, 'les' => true, 'un' => true, 'une' => true, 'des' => true, 'du' => true, 'de' => true,
        'et' => true, 'ou' => true, 'dans' => true, 'sur' => true, 'pour' => true, 'par' => true, 'avec' => true,
        'sans' => true, 'être' => true, 'avoir' => true, 'votre' => true, 'notre' => true, 'vous' => true, 'nous' => true,
        'ils' => true, 'elles' => true, 'son' => true, 'sa' => true, 'ses' => true, 'ce' => true, 'cette' => true,
        'ces' => true, 'qui' => true, 'que' => true, 'dont' => true, 'où' => true, 'au' => true, 'aux' => true,
        'est' => true, 'sont' => true, 'été' => true, 'sera' => true, 'seront' => true, 'plus' => true, 'moins' => true,
        'très' => true, 'tout' => true, 'tous' => true, 'toute' => true, 'toutes' => true, 'comme' => true, 'aussi' => true,
        'chez' => true, 'entre' => true, 'vers' => true, 'lors' => true, 'afin' => true, 'ainsi' => true,
        'poste' => true, 'mission' => true, 'profil' => true, 'recherche' => true, 'recherchons' => true, 'candidat' => true,
        'candidature' => true, 'offre' => true, 'emploi' => true, 'stage' => true, 'alternance' => true,
    ];

    public function __construct(
        private readonly CvDocumentTextExtractor $cvDocumentTextExtractor,
    ) {
    }

    /**
     * @return float|null Pourcentage 0–100, ou null si le score n’est pas calculable (CV illisible, aucun mot-clé utile).
     */
    public function computeMatchPercentage(string $cvAbsolutePath, JobOffer $jobOffer): ?float
    {
        $cvText = $this->cvDocumentTextExtractor->extractFromAbsolutePath($cvAbsolutePath);
        if ($cvText === null || $cvText === '') {
            return null;
        }

        $keywords = $this->extractKeywordsFromJobOffer($jobOffer);
        if ($keywords === []) {
            return null;
        }

        $cvLower = mb_strtolower($cvText);
        $matched = 0;
        foreach ($keywords as $kw) {
            if ($this->keywordMatchesCv($kw, $cvLower)) {
                ++$matched;
            }
        }

        $total = \count($keywords);
        if ($total === 0) {
            return null;
        }

        return round(100.0 * $matched / $total, 2);
    }

    /**
     * @return list<string>
     */
    private function extractKeywordsFromJobOffer(JobOffer $jobOffer): array
    {
        $ordered = [];

        $skills = $jobOffer->getSkillsRequired();
        if (\is_string($skills) && trim($skills) !== '') {
            foreach (preg_split('/[,;\n|\/]+/u', $skills) as $part) {
                $p = trim($part);
                if (mb_strlen($p) < 2) {
                    continue;
                }
                $k = mb_strtolower($p);
                if (!isset(self::STOP_WORDS[$k])) {
                    $ordered[$k] = true;
                }
            }
        }

        $corpus = $this->buildJobOfferCorpus($jobOffer);
        $corpusLower = mb_strtolower($corpus);

        foreach (['c#', 'c++', 'f#', '.net', 'node.js', 'vue.js', 'react.js', 'angular.js', 'asp.net', 'objective-c'] as $literal) {
            if (str_contains($corpusLower, $literal)) {
                $ordered[$literal] = true;
            }
        }

        if (preg_match_all('/[\p{L}][\p{L}\p{N}#+.@-]{1,}/u', $corpusLower, $m)) {
            foreach ($m[0] as $w) {
                if (mb_strlen($w) < 2 || isset(self::STOP_WORDS[$w]) || ctype_digit($w)) {
                    continue;
                }
                $ordered[$w] = true;
                if (\count($ordered) >= 120) {
                    break;
                }
            }
        }

        return array_slice(array_keys($ordered), 0, 100);
    }

    private function buildJobOfferCorpus(JobOffer $jobOffer): string
    {
        $parts = array_filter(
            [
                $jobOffer->getTitle(),
                $jobOffer->getDescription(),
                $jobOffer->getRequirements(),
                $jobOffer->getSkillsRequired(),
                $jobOffer->getExperienceLevel(),
                $jobOffer->getLocation(),
                $jobOffer->getWorkType(),
                $jobOffer->getBenefits(),
            ],
            static fn ($s): bool => \is_string($s) && trim($s) !== '',
        );

        return implode("\n", $parts);
    }

    private function keywordMatchesCv(string $keyword, string $cvLower): bool
    {
        if (str_contains($keyword, ' ') || str_contains($keyword, '-')) {
            return str_contains($cvLower, $keyword);
        }

        if (preg_match('/[#+.]|\+\+/', $keyword)) {
            return str_contains($cvLower, $keyword);
        }

        $q = preg_quote($keyword, '/');

        return (bool) preg_match('/(?<![\p{L}\p{N}])'.$q.'(?![\p{L}\p{N}])/u', $cvLower);
    }
}
