<?php

declare(strict_types=1);

namespace App\Recruitment\CvBuilder;

final readonly class CvBuilderData
{
    /**
     * @param list<array{degree: string, institution: string, year: string}> $education
     * @param list<array{jobTitle: string, company: string, duration: string, description: string}> $experience
     */
    public function __construct(
        public string $fullName,
        public string $professionalTitle,
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

    /** @return list<string> */
    public function skillsAsList(): array
    {
        return $this->splitList($this->skills);
    }

    /** @return list<string> */
    public function languagesAsList(): array
    {
        return $this->languages === null ? [] : $this->splitList($this->languages);
    }

    /** @return list<string> */
    private function splitList(string $value): array
    {
        $parts = preg_split('/[,;\n]+/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $items = [];
        foreach ($parts as $part) {
            $item = trim($part);
            if ($item !== '') {
                $items[$item] = true;
            }
        }

        return array_keys($items);
    }
}
