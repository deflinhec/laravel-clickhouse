<?php

declare(strict_types=1);

namespace Deflinhec\LaravelClickHouse\Tests\Unit\Database\Eloquent;

use Deflinhec\LaravelClickHouse\Database\Connection;
use Deflinhec\LaravelClickHouse\Tests\FirstTableEntry;
use Deflinhec\LaravelClickHouse\Tests\TestCase;

class FirstTableTest extends TestCase
{
    /**
     * @var Connection
     */
    protected $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = $this->getConnection('clickhouse');
    }

    public function testPaginate(): void
    {
        $this->connection->statement('DROP TABLE IF EXISTS my_first_table');

        $result = $this->connection->statement('CREATE TABLE my_first_table
(
    user_id UInt32,
    message String,
    timestamp DateTime,
    metric Float32
)
ENGINE = MergeTree()
PRIMARY KEY (user_id, timestamp)');
        self::assertTrue($result);

        self::assertTrue(FirstTableEntry::query()->insert([
            'user_id' => 1,
            'message' => 'hello world',
            'timestamp' => new \DateTime(),
            'metric' => 42,
        ]));

        self::assertCount(1, FirstTableEntry::query()->paginate()->items());
    }
}
