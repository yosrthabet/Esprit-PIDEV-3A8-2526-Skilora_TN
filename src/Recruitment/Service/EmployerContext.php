<?php

namespace App\Recruitment\Service;

use App\Entity\User;
use App\Recruitment\Entity\Company;
use App\Recruitment\Repository\CompanyRepository;

final class EmployerContext
{
    public function __construct(
        private readonly CompanyRepository $companyRepository,
    ) {
    }

    public function getCompanyForEmployer(User $user): ?Company
    {
        return $this->companyRepository->findOwnedByUser($user);
    }
}
