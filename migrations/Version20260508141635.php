<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260508141635 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE formation_materials (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, resource_url VARCHAR(2048) NOT NULL, kind VARCHAR(40) NOT NULL, position INT NOT NULL, created_at DATETIME NOT NULL, module_id INT NOT NULL, INDEX IDX_516A4668AFC2B591 (module_id), INDEX idx_formation_material_module_position (module_id, position), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE formation_reviews (id INT AUTO_INCREMENT NOT NULL, rating INT NOT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, formation_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_2C8FAE745200282E (formation_id), INDEX IDX_2C8FAE74A76ED395 (user_id), INDEX idx_formation_review_rating (formation_id, rating), UNIQUE INDEX uniq_formation_review_user (formation_id, user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE formation_materials ADD CONSTRAINT FK_516A4668AFC2B591 FOREIGN KEY (module_id) REFERENCES formation_modules (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE formation_reviews ADD CONSTRAINT FK_2C8FAE745200282E FOREIGN KEY (formation_id) REFERENCES formations (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE formation_reviews ADD CONSTRAINT FK_2C8FAE74A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE formation_materials DROP FOREIGN KEY FK_516A4668AFC2B591');
        $this->addSql('ALTER TABLE formation_reviews DROP FOREIGN KEY FK_2C8FAE745200282E');
        $this->addSql('ALTER TABLE formation_reviews DROP FOREIGN KEY FK_2C8FAE74A76ED395');
        $this->addSql('DROP TABLE formation_materials');
        $this->addSql('DROP TABLE formation_reviews');
    }
}
