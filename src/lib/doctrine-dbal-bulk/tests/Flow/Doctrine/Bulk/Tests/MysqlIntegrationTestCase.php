<?php

declare(strict_types=1);

namespace Flow\Doctrine\Bulk\Tests;

use Doctrine\DBAL\DriverManager;
use Flow\Doctrine\Bulk\Tests\Context\DatabaseContext;
use PHPUnit\Framework\TestCase;

abstract class MysqlIntegrationTestCase extends TestCase
{
    protected DatabaseContext $databaseContext;

    protected function setUp() : void
    {
        $this->databaseContext = new DatabaseContext(DriverManager::getConnection(['url' => \getenv('MYSQL_DATABASE_URL')]));
        $this->databaseContext->connection()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    protected function tearDown() : void
    {
        $this->databaseContext->dropAllTables();
    }
}
