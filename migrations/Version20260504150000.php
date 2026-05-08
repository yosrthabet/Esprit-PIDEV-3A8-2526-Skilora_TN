<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Fix invalid FeedSource enum values in job_offers table.
 * 
 * Issue: job_offers.feed_source contains values like "Reddit r/forhire", "Reddit r/jobs", etc.
 * which are not valid enum backing values. Valid values are: aneti, linkedin, indeed, emploi_tn,
 * tanitjobs, keejob, reddit, manual.
 * 
 * This migration converts all Reddit entries to the valid 'reddit' enum value.
 */
final class Version20260504150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix invalid FeedSource enum values: convert Reddit r/* to reddit';
    }

    public function up(Schema $schema): void
    {
        // Convert all "Reddit r/*" values to "reddit"
        $this->addSql("UPDATE job_offers SET feed_source = 'reddit' WHERE feed_source LIKE 'Reddit r/%'");
        $this->addSql("UPDATE job_offers SET feed_source = 'reddit' WHERE feed_source = 'reddit' AND feed_source != 'reddit'");
    }

    public function down(Schema $schema): void
    {
        // Cannot reliably reverse this migration without knowing original subreddit names
        // If needed, restore from backup or manually update records
    }
}
