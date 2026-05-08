<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Doctrine\DBAL\Connection;

final class DoctrineSessionConfigSubscriber
{
    public function postConnect(object $event): void
    {
        if (!method_exists($event, 'getConnection')) {
            return;
        }

        $connection = $event->getConnection();
        if (!$connection instanceof Connection) {
            return;
        }

        $connection->executeStatement("SET time_zone = '+00:00'");
        $connection->executeStatement("SET SESSION sql_mode = CONCAT_WS(',', @@sql_mode, 'NO_ZERO_DATE', 'NO_ZERO_IN_DATE')");
    }
}
