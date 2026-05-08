<?php

declare(strict_types=1);

namespace App\Recruitment\Service;

use App\Entity\User;
use App\Enum\Currency;
use App\Enum\ExperienceLevel;
use App\Enum\JobOfferStatus;
use App\Enum\WorkType;
use App\Recruitment\Entity\Company;
use App\Recruitment\Entity\JobOffer;
use App\Recruitment\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\InputBag;

class EmployerJobOfferService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CompanyRepository $companyRepository,
    ) {
    }

    public function getOrCreateCompany(User $owner): Company
    {
        $company = $this->companyRepository->findOneForOwner($owner);
        if ($company !== null) {
            return $company;
        }

        $company = (new Company())
            ->setOwner($owner)
            ->setName($owner->getFullName() ? $owner->getFullName() . ' Studio' : $owner->getUsername() . ' Company')
            ->setLocation('Tunisia');
        $this->entityManager->persist($company);
        $this->entityManager->flush();

        return $company;
    }

    /** @param InputBag<bool|float|int|string> $data */
    public function createFromRequest(User $owner, InputBag $data): JobOffer
    {
        $company = $this->getOrCreateCompany($owner);
        $jobOffer = (new JobOffer())->setCompany($company)->setCompanyName($company->getName());

        $this->applyRequestData($jobOffer, $data);
        $this->entityManager->persist($jobOffer);
        $this->entityManager->flush();

        return $jobOffer;
    }

    /** @param InputBag<bool|float|int|string> $data */
    public function updateFromRequest(User $owner, JobOffer $jobOffer, InputBag $data): void
    {
        $this->assertOwner($owner, $jobOffer);
        $this->applyRequestData($jobOffer, $data);
        $jobOffer->touch();
        $this->entityManager->flush();
    }

    public function close(User $owner, JobOffer $jobOffer): void
    {
        $this->assertOwner($owner, $jobOffer);
        $jobOffer->close();
        $this->entityManager->flush();
    }

    public function reopen(User $owner, JobOffer $jobOffer): void
    {
        $this->assertOwner($owner, $jobOffer);
        $jobOffer->reopen();
        $this->entityManager->flush();
    }

    public function delete(User $owner, JobOffer $jobOffer): void
    {
        $this->assertOwner($owner, $jobOffer);
        $this->entityManager->remove($jobOffer);
        $this->entityManager->flush();
    }

    public function duplicate(User $owner, JobOffer $jobOffer): JobOffer
    {
        $this->assertOwner($owner, $jobOffer);
        $copy = (new JobOffer())
            ->setCompany($jobOffer->getCompany())
            ->setCompanyName($jobOffer->getCompanyName())
            ->setTitle($jobOffer->getTitle() . ' (copy)')
            ->setDescription($jobOffer->getDescription())
            ->setRequirements($jobOffer->getRequirements())
            ->setSkillsRequired($jobOffer->getSkillsRequired())
            ->setBenefits($jobOffer->getBenefits())
            ->setLocation($jobOffer->getLocation())
            ->setWorkType($jobOffer->getWorkType())
            ->setExperienceLevel($jobOffer->getExperienceLevel())
            ->setMinSalary($jobOffer->getMinSalary())
            ->setMaxSalary($jobOffer->getMaxSalary())
            ->setCurrency($jobOffer->getCurrency())
            ->setStatus(JobOfferStatus::DRAFT);
        $this->entityManager->persist($copy);
        $this->entityManager->flush();

        return $copy;
    }

    public function assertOwner(User $owner, JobOffer $jobOffer): void
    {
        if ($jobOffer->getCompany()?->getOwner()->getId() !== $owner->getId()) {
            throw new \RuntimeException('You cannot manage this job offer.');
        }
    }

    /** @param InputBag<bool|float|int|string> $data */
    private function applyRequestData(JobOffer $jobOffer, InputBag $data): void
    {
        $deadline = trim($data->getString('deadline'));

        $jobOffer
            ->setTitle(trim($data->getString('title')))
            ->setDescription(trim($data->getString('description')) ?: null)
            ->setRequirements(trim($data->getString('requirements')) ?: null)
            ->setSkillsRequired(trim($data->getString('skills')) ?: null)
            ->setBenefits(trim($data->getString('benefits')) ?: null)
            ->setLocation(trim($data->getString('location')) ?: null)
            ->setWorkType(WorkType::tryFrom($data->getString('work_type')))
            ->setExperienceLevel(ExperienceLevel::tryFrom($data->getString('experience_level')))
            ->setMinSalary(trim($data->getString('min_salary')) ?: null)
            ->setMaxSalary(trim($data->getString('max_salary')) ?: null)
            ->setCurrency(Currency::tryFrom($data->getString('currency')) ?? Currency::TND)
            ->scheduleExpiry($deadline !== '' ? new \DateTimeImmutable($deadline) : null)
            ->setStatus($data->getString('intent') === 'draft' ? JobOfferStatus::DRAFT : JobOfferStatus::OPEN);
    }
}
