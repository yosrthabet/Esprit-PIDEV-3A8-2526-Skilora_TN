<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415213000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add certificate verification UUID and unique index';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE certificates ADD COLUMN IF NOT EXISTS verification_id VARCHAR(36) DEFAULT NULL");
        $this->addSql("UPDATE certificates SET verification_id = UUID() WHERE verification_id IS NULL OR verification_id = ''");
        $this->addSql("ALTER TABLE certificates MODIFY verification_id VARCHAR(36) NOT NULL");
        $this->addSql("CREATE UNIQUE INDEX uniq_certificate_verification_id ON certificates (verification_id)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP INDEX uniq_certificate_verification_id ON certificates");
        $this->addSql("ALTER TABLE certificates DROP COLUMN verification_id");
    }
}
