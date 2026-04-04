<?php

namespace App\Recruitment\Repository;

use App\Entity\User;
use App\Recruitment\Entity\Company;
use App\Recruitment\Entity\JobOffer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Company>
 */
class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
    }

    public function findOwnedByUser(User $user): ?Company
    {
        return $this->findOneBy(['owner' => $user]);
    }

    /**
     * Identifiants des fiches `companies` pour cet utilisateur, lus en SQL sur `owner_id`
     * (évite tout écart Doctrine / instance User).
     *
     * @return list<int>
     */
    public function findCompanyIdsByOwnerUserId(int $ownerUserId): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $rows = $conn->fetchFirstColumn(
            'SELECT id FROM companies WHERE owner_id = ? ORDER BY id ASC',
            [$ownerUserId],
        );

        return array_map(static fn ($v) => (int) $v, $rows);
    }

    /**
     * @return list<int>
     */
    public function findIdsOwnedByUser(User $user): array
    {
        $id = $user->getId();
        if ($id === null) {
            return [];
        }

        return $this->findCompanyIdsByOwnerUserId($id);
    }

    /**
     * Le libellé entreprise sur l’offre (company_name) correspond-il à une fiche entreprise dont cet utilisateur est propriétaire ?
     * Utile quand job_offers.company_id pointe vers une mauvaise ligne alors que company_name est correct.
     */
    public function employerOwnsCompanyName(User $owner, ?string $jobCompanyDisplayName): bool
    {
        if ($jobCompanyDisplayName === null || trim($jobCompanyDisplayName) === '') {
            return false;
        }

        $target = mb_strtolower(trim($jobCompanyDisplayName));
        foreach ($this->findBy(['owner' => $owner], ['id' => 'ASC']) as $company) {
            if (mb_strtolower(trim($company->getName())) === $target) {
                return true;
            }
        }

        return false;
    }

    /**
     * L’employeur peut-il gérer cette offre : même règle que la liste (company_id ∈ entreprises SQL) ou secours sur company_name.
     */
    public function employerOwnsJobOfferDisplay(User $employer, JobOffer $job): bool
    {
        $uid = $employer->getId();
        if ($uid === null) {
            return false;
        }

        $ownedIds = $this->findCompanyIdsByOwnerUserId($uid);
        $jc = $job->getCompany()?->getId();
        if ($jc !== null && \in_array($jc, $ownedIds, true)) {
            return true;
        }

        return $this->employerOwnsCompanyName($employer, $job->getCompanyName());
    }

    /**
     * Première fiche entreprise possédée dont le nom correspond au libellé d’offre (pour corriger company_id).
     */
    public function findFirstOwnedCompanyMatchingDisplayName(User $owner, ?string $jobCompanyDisplayName): ?Company
    {
        if ($jobCompanyDisplayName === null || trim($jobCompanyDisplayName) === '') {
            return null;
        }

        $target = mb_strtolower(trim($jobCompanyDisplayName));
        foreach ($this->findBy(['owner' => $owner], ['id' => 'ASC']) as $company) {
            if (mb_strtolower(trim($company->getName())) === $target) {
                return $company;
            }
        }

        return null;
    }
}
