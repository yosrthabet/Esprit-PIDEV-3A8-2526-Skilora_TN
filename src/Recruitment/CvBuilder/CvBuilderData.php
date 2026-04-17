<?php

declare(strict_types=1);

namespace App\Recruitment\CvBuilder;

/**
 * Données structurées du formulaire « Créer mon CV » (aucun contenu inventé — uniquement saisie utilisateur).
 */
final readonly class CvBuilderData
{
    /**
     * @param list<array{degree: string, institution: string, year: string}> $education
     * @param list<array{jobTitle: string, company: string, duration: string, description: string}> $experience
     */
    public function __construct(
        public string $fullName,
        public string $email,
        public ?string $phone,
        public ?string $address,
        public string $professionalSummary,
        public array $education,
        public array $experience,
        public string $skills,
        public ?string $languages,
        public string $template,
        public ?string $photoDataUri,
    ) {
    }

    /**
     * @return list<string>
     */
    public function skillsAsList(): array
    {
        $parts = preg_split('/[,;\n]+/', $this->skills, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_map('trim', $parts)));
    }

    /**
     * @return list<string>
     */
    public function languagesAsList(): array
    {
        if ($this->languages === null || trim($this->languages) === '') {
            return [];
        }
        $parts = preg_split('/[,;\n]+/', $this->languages, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_map('trim', $parts)));
    }
}
