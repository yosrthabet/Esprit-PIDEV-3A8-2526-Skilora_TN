<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Table {@code interviews} parfois présente sans le même schéma que l’entité (ex. sans {@code scheduled_at}),
 * notamment si elle a été créée à la main avant les migrations Doctrine.
 */
final class Version20260407120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'interviews: colonnes manquantes alignées sur JobInterview (scheduled_at, format, etc.)';
    }

    public function up(Schema $schema): void
    {
        $this->skipIf(
            !$this->sm->tablesExist(['interviews']),
            'Table interviews absente',
        );

        $colNames = $this->interviewsColumnNamesLower();
        $platform = $this->connection->getDatabasePlatform();
        $mysql = $platform instanceof AbstractMySQLPlatform;
        $sqlite = $platform instanceof SQLitePlatform;
        $pgsql = $platform instanceof PostgreSQLPlatform;

        if (!isset($colNames['scheduled_at'])) {
            if ($sqlite) {
                $this->addSql('ALTER TABLE interviews ADD COLUMN scheduled_at DATETIME DEFAULT NULL');
            } elseif ($pgsql) {
                $this->addSql('ALTER TABLE interviews ADD scheduled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
            } else {
                $this->addSql("ALTER TABLE interviews ADD scheduled_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
            }
        }

        if (!isset($colNames['format'])) {
            if ($sqlite) {
                $this->addSql("ALTER TABLE interviews ADD COLUMN format VARCHAR(20) NOT NULL DEFAULT 'ONLINE'");
            } else {
                $this->addSql("ALTER TABLE interviews ADD format VARCHAR(20) NOT NULL DEFAULT 'ONLINE'");
            }
        }

        if (!isset($colNames['location'])) {
            if ($sqlite) {
                $this->addSql('ALTER TABLE interviews ADD COLUMN location VARCHAR(150) DEFAULT NULL');
            } else {
                $this->addSql('ALTER TABLE interviews ADD location VARCHAR(150) DEFAULT NULL');
            }
        }

        if (!isset($colNames['lifecycle_status'])) {
            if ($sqlite) {
                $this->addSql("ALTER TABLE interviews ADD COLUMN lifecycle_status VARCHAR(20) NOT NULL DEFAULT 'SCHEDULED'");
            } else {
                $this->addSql("ALTER TABLE interviews ADD lifecycle_status VARCHAR(20) NOT NULL DEFAULT 'SCHEDULED'");
            }
        }

        if (!isset($colNames['created_at'])) {
            if ($sqlite) {
                $this->addSql('ALTER TABLE interviews ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
            } elseif ($pgsql) {
                $this->addSql('ALTER TABLE interviews ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP');
            } else {
                $this->addSql("ALTER TABLE interviews ADD created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '(DC2Type:datetime_immutable)'");
            }
        }

        if (!isset($colNames['updated_at'])) {
            if ($sqlite) {
                $this->addSql('ALTER TABLE interviews ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
            } elseif ($pgsql) {
                $this->addSql('ALTER TABLE interviews ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP');
            } else {
                $this->addSql("ALTER TABLE interviews ADD updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '(DC2Type:datetime_immutable)'");
            }
        }

        // Si une ancienne colonne porte la date sous un autre nom, recopier une fois vers scheduled_at.
        $finalCols = $this->interviewsColumnNamesLower();
        if (isset($finalCols['scheduled_at'])) {
            foreach (['interview_date', 'date_interview', 'datetime_interview', 'interview_at'] as $legacy) {
                if (isset($finalCols[$legacy])) {
                    if ($mysql) {
                        $this->addSql("UPDATE interviews SET scheduled_at = `{$legacy}` WHERE scheduled_at IS NULL AND `{$legacy}` IS NOT NULL");
                    } elseif ($pgsql) {
                        $lq = $this->connection->quoteIdentifier($legacy);
                        $this->addSql("UPDATE interviews SET scheduled_at = {$lq} WHERE scheduled_at IS NULL AND {$lq} IS NOT NULL");
                    } else {
                        $this->addSql("UPDATE interviews SET scheduled_at = {$legacy} WHERE scheduled_at IS NULL AND {$legacy} IS NOT NULL");
                    }
                    break;
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
    }

    /**
     * @return array<string, true>
     */
    private function interviewsColumnNamesLower(): array
    {
        $out = [];
        foreach ($this->sm->listTableColumns('interviews') as $c) {
            $out[strtolower($c->getName())] = true;
        }

        return $out;
    }
}
