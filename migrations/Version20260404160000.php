<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260404160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create community_posts for community feed (author, content, optional image URL).';
    }

    public function up(Schema $schema): void
    {
        $this->skipIf(
            $this->connection->createSchemaManager()->tablesExist(['community_posts']),
            'Table community_posts already exists'
        );

        $this->addSql(<<<'SQL'
            CREATE TABLE community_posts (
                id INT AUTO_INCREMENT NOT NULL,
                author_id INT NOT NULL,
                content LONGTEXT NOT NULL,
                image_url VARCHAR(2048) DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);
        $this->addSql('ALTER TABLE community_posts ADD CONSTRAINT FK_community_post_author FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE community_posts DROP FOREIGN KEY FK_community_post_author');
        $this->addSql('DROP TABLE community_posts');
    }
}
