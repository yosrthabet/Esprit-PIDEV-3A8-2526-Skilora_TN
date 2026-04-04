<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Statuts candidature : IN_PROGRESS (En cours), ACCEPTED, REJECTED — migration des anciens codes.
 */
final class Version20260401190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize job_applications.status to IN_PROGRESS / ACCEPTED / REJECTED';
    }

    public function up(Schema $schema): void
    {
        $this->skipIf(
            !$this->sm->tablesExist(['job_applications']),
            'Table job_applications absente',
        );

        // Anciens envois → En cours
        $this->addSql("UPDATE job_applications SET status = 'IN_PROGRESS' WHERE status NOT IN ('ACCEPTED', 'REJECTED') OR status IS NULL OR status = ''");
    }

    public function down(Schema $schema): void
    {
        // Pas de retour fiable vers SUBMITTED : no-op
    }
}
