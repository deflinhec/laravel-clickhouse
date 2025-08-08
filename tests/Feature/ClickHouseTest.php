<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\TestCase;

class ClickHouseTest extends TestCase
{
    /**
     * ClickHouse 客戶端實例
     */
    protected $client;

    /**
     * ClickHouse 配置
     */
    protected $config;

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    /**
     * 設定 ClickHouse 客戶端
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->config = config('clickhouse.connections.default');

        $this->client = app(\ClickHouseDB\Client::class);
    }

    /**
     * 測試 ClickHouse 基本連接
     *
     * @return void
     */
    public function test_basic_connection()
    {
        $result = $this->client->select('SELECT 1 as test');
        $this->assertEquals(1, $result->fetchOne('test'));
    }

    /**
     * 測試資料庫資訊
     *
     * @return void
     */
    public function test_database_info()
    {
        $result = $this->client->select(<<<SQL
            SELECT currentDatabase() as database
        SQL);
        $database = $result->fetchOne('database');
        $this->assertEquals($this->config['database'], $database);
    }

    /**
     * 測試表列表
     *
     * @return void
     */
    public function test_table_list()
    {
        $result = $this->client->select(<<<SQL
            SHOW TABLES FROM {$this->config['database']}
        SQL);
        $tables = $result->rows();

        $this->assertNotEmpty($tables);

        $tableNames = array_column($tables, 'name');
        $this->assertContains('united_tickets_mariadb_raw', $tableNames);
        $this->assertContains('united_tickets_nested', $tableNames);
    }
}
