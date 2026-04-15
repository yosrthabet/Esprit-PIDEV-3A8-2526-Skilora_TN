<?php

declare(strict_types=1);

namespace App\Recruitment\Sql;

/**
 * Filtre employeur pour les candidatures : uniquement via les offres de l’entreprise.
 * Ne filtre jamais par {@code candidate_id}.
 *
 * Si le paramètre {@code recruitment.employer_sees_all_candidatures} est à {@code true},
 * le gateway n’utilise pas ce fragment (clause {@code 1=1} à la place) : tout employeur voit toutes les candidatures.
 *
 * Sinon, une offre est « à l’employeur » si {@code companies.owner_id} correspond et :
 * soit {@code job_offers.company_id} pointe vers cette entreprise,
 * soit le libellé {@code job_offers.company_name} correspond au nom d’une de ses entreprises
 * (cas hérités où {@code company_id} est incorrect ou vide).
 */
final class EmployerApplicationsScopeSql
{
    /**
     * Fragment SQL booléen (avec un paramètre {@code ?} pour l’id utilisateur employeur).
     */
    public static function jobOfferOwnedByEmployer(string $jobOffersAlias = 'j'): string
    {
        return <<<SQL
EXISTS (
    SELECT 1 FROM companies co
    WHERE co.owner_id = ?
      AND (
          co.id = {$jobOffersAlias}.company_id
          OR (
              {$jobOffersAlias}.company_name IS NOT NULL
              AND TRIM({$jobOffersAlias}.company_name) <> ''
              AND LOWER(TRIM(co.name)) = LOWER(TRIM({$jobOffersAlias}.company_name))
          )
      )
)
SQL;
    }
}
