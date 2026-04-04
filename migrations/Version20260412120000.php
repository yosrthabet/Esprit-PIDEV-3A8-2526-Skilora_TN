<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260412120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create formation_enrollments (user ↔ formation inscriptions).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE formation_enrollments (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, formation_id INT NOT NULL, enrolled_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_FE_USER (user_id), INDEX IDX_FE_FORMATION (formation_id), UNIQUE INDEX uniq_user_formation (user_id, formation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE formation_enrollments ADD CONSTRAINT FK_FE_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE formation_enrollments ADD CONSTRAINT FK_FE_FORMATION FOREIGN KEY (formation_id) REFERENCES formations (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE formation_enrollments');
    }
}
