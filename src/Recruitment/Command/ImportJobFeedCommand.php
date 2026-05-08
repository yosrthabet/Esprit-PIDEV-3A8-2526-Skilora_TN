<?php

declare(strict_types=1);

namespace App\Recruitment\Command;

use App\Enum\FeedSource;
use App\Enum\JobOfferStatus;
use App\Recruitment\Entity\JobOffer;
use App\Recruitment\Repository\JobOfferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:recruitment:import-feed', description: 'Import external job feed JSON into recruitment offers.')]
class ImportJobFeedCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly JobOfferRepository $jobOfferRepository,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('skip-crawl', null, InputOption::VALUE_NONE, 'Use existing data/job_feed.json')
            ->addOption('json', null, InputOption::VALUE_REQUIRED, 'Path to feed JSON')
            ->addOption('max-age', null, InputOption::VALUE_REQUIRED, 'Maximum listing age in days', '45');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $jsonOption = $input->getOption('json');
        $jsonPath = is_string($jsonOption) ? $jsonOption : $this->projectDir . '/data/job_feed.json';

        if (!$input->getOption('skip-crawl')) {
            $script = $this->projectDir . '/python/job_feed_crawler.py';
            $cmd = 'python3 ' . escapeshellarg($script) . ' 2>&1';
            $lines = [];
            $exitCode = 0;
            exec($cmd, $lines, $exitCode);
            $io->text($lines);
            if ($exitCode !== 0) {
                $io->error('Crawler failed.');
                return Command::FAILURE;
            }
        }

        if (!is_file($jsonPath)) {
            $io->error('Feed JSON not found: ' . $jsonPath);
            return Command::FAILURE;
        }

        $data = json_decode((string) file_get_contents($jsonPath), true);
        $jobs = is_array($data) && is_array($data['jobs'] ?? null) ? $data['jobs'] : [];
        $maxAgeOption = $input->getOption('max-age');
        $maxAge = is_numeric($maxAgeOption) ? (int) $maxAgeOption : 45;
        $cutoff = (new \DateTimeImmutable())->modify('-' . $maxAge . ' days');
        $imported = 0;
        $skipped = 0;

        foreach ($jobs as $row) {
            if (!is_array($row)) {
                continue;
            }
            $source = $this->sourceFromLabel($this->stringValue($row['source'] ?? 'rss'));
            $sourceId = mb_substr($this->stringValue($row['raw_id'] ?? $row['url'] ?? ''), 0, 255);
            if ($sourceId === '' || isset($this->jobOfferRepository->findExistingFeedIds($source)[$sourceId])) {
                $skipped++;
                continue;
            }
            $postedAt = $this->postedAt($this->stringValue($row['posted_date'] ?? ''));
            if ($postedAt < $cutoff) {
                $skipped++;
                continue;
            }
            $job = (new JobOffer())
                ->setTitle(mb_substr($this->stringValue($row['title'] ?? 'Untitled job'), 0, 180))
                ->setDescription($this->stringValue($row['description'] ?? ''))
                ->setLocation(mb_substr($this->stringValue($row['location'] ?? ''), 0, 140) ?: null)
                ->setCompanyName($this->companyFromSource($this->stringValue($row['source'] ?? 'External')))
                ->setStatus(JobOfferStatus::OPEN)
                ->setFeedSource($source)
                ->setFeedSourceId($sourceId)
                ->setFeedUrl(mb_substr($this->stringValue($row['apply_url'] ?? $row['url'] ?? ''), 0, 700) ?: null)
                ->recordPostedAt($postedAt)
                ->setSourceQuality($this->quality($row));
            $this->entityManager->persist($job);
            $imported++;
        }

        $this->entityManager->flush();
        $io->success(sprintf('Imported %d jobs, skipped %d.', $imported, $skipped));

        return Command::SUCCESS;
    }

    private function sourceFromLabel(string $source): FeedSource
    {
        $source = mb_strtolower($source);
        return match (true) {
            str_contains($source, 'aneti') => FeedSource::ANETI,
            str_contains($source, 'reddit') => FeedSource::REDDIT,
            str_contains($source, 'linkedin') => FeedSource::LINKEDIN_RSS,
            default => FeedSource::RSS,
        };
    }

    private function postedAt(string $date): \DateTimeImmutable
    {
        try {
            return $date !== '' ? new \DateTimeImmutable($date) : new \DateTimeImmutable();
        } catch (\Throwable) {
            return new \DateTimeImmutable();
        }
    }

    /** @param array<mixed> $row */
    private function quality(array $row): int
    {
        $score = 40;
        if (($row['apply_url'] ?? '') !== '') { $score += 20; }
        if (($row['description'] ?? '') !== '') { $score += 15; }
        if (($row['location'] ?? '') !== '') { $score += 10; }
        if (($row['posted_date'] ?? '') !== '') { $score += 10; }
        return min(100, $score);
    }

    private function companyFromSource(string $source): string
    {
        return match (true) {
            str_contains(mb_strtolower($source), 'aneti') => 'ANETI Tunisia',
            str_contains(mb_strtolower($source), 'reddit') => 'Reddit Hiring',
            default => $source !== '' ? $source : 'External Feed',
        };
    }

    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
