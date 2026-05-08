<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Hydration fails when job_offers.feed_source contains strings that are not {@see \App\Enum\FeedSource} cases
 * (e.g. "Remote OK (RSS)" from legacy imports). Normalize unknown values to `manual`.
 */
final class Version20260505104500 extends AbstractMigration
{
    private const VALID_FEED_SOURCES = "('aneti','linkedin','indeed','emploi_tn','tanitjobs','keejob','reddit','manual')";

    public function getDescription(): string
    {
        return 'Normalize invalid job_offers.feed_source values to manual for FeedSource enum hydration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'UPDATE job_offers SET feed_source = \'manual\' WHERE feed_source IS NOT NULL AND feed_source NOT IN '
            .self::VALID_FEED_SOURCES,
        );
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Cannot restore previous free-text feed_source values.');
    }
}
