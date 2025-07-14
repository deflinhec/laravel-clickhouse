<?php

declare(strict_types=1);

namespace Deflinhec\LaravelClickHouse\Tests\Unit\Database\Eloquent;

use Deflinhec\LaravelClickHouse\Database\Connection;
use Deflinhec\LaravelClickHouse\Tests\FirstTableEntry;
use Deflinhec\LaravelClickHouse\Tests\TestCase;
use Illuminate\Database\DatabaseManager;
use Tinderbox\ClickhouseBuilder\Query\Column;

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

        /** @var Mock|DatabaseManager $resolver */
        $resolver = $this->mock(DatabaseManager::class);
        $resolver->shouldReceive('connection')
            ->andReturn($this->connection);

        FirstTableEntry::setConnectionResolver($resolver);
    }

    public function testCreateTable(): void
    {
        $this->connection->statement(<<<SQL
            DROP TABLE IF EXISTS my_first_table
        SQL);

        $result = $this->connection->statement(<<<SQL
            CREATE TABLE my_first_table
            (
                user_id UInt32,
                message String,
                timestamp DateTime,
                metric Float32
            )
            ENGINE = MergeTree()
            PRIMARY KEY (user_id, timestamp)
        SQL);

        self::assertTrue($result);
    }

    public function testInsert(): void
    {
        $this->testCreateTable();

        self::assertTrue(FirstTableEntry::query()->insert([
            'user_id' => 1,
            'message' => 'hello world',
            'timestamp' => new \DateTime(),
            'metric' => 42,
        ]));
    }

    public function testCreateModel(): void
    {
        $this->testCreateTable();

        $model = FirstTableEntry::create([
            'user_id' => 1,
            'message' => 'hello world',
            'timestamp' => new \DateTime(),
            'metric' => 42,
        ]);

        self::assertInstanceOf(FirstTableEntry::class, $model);
        self::assertEquals(1, $model->user_id);
        self::assertEquals('hello world', $model->message);
        self::assertEquals(42, $model->metric);
    }

    public function testSum(): void
    {
        $this->testCreateTable();

        for ($i = 0; $i < 2; $i++) {
            FirstTableEntry::create([
                'user_id' => 1,
                'message' => 'hello world',
                'timestamp' => new \DateTime(),
                'metric' => 42,
            ]);
        }

        $result = FirstTableEntry::query()
            ->select(function (Column $column) {
                return $column->sum('metric')
                    ->as('sum_metric');
            }, 'user_id')
            ->groupBy('user_id')
            ->get();

        self::assertCount(1, $result);
        self::assertEquals(84, $result->first()->sum_metric);
    }

    public function testPaginate(): void
    {
        $this->testCreateModel();

        $pagination = FirstTableEntry::query()->paginate();
        self::assertCount(1, $pagination->items());
        self::assertEquals(1, $pagination->total());
        self::assertEquals(1, $pagination->currentPage());
        self::assertEquals(1, $pagination->lastPage());
        self::assertEquals(1, $pagination->firstItem());
        self::assertEquals(1, $pagination->lastItem());
    }
}
