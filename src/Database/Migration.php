<?php

namespace Deflinhec\LaravelClickHouse\Database;

abstract class Migration
{
    /**
     * ClickHouse 客戶端實例
     *
     * @var \ClickHouseDB\Client
     */
    protected $client;

    /**
     * 建構函數
     */
    public function __construct()
    {
        $this->client = app(\ClickHouseDB\Client::class);
    }

    /**
     * Run the migrations.
     *
     * @return string
     */
    abstract public function up();

    /**
     * Reverse the migrations.
     *
     * @return string
     */
    abstract public function down();

    /**
     * Check if the engine exists, if not, use the fallback engine
     *
     * @param string $engine
     * @param string $fallback
     * @return string
     */
    protected function engineExists(string $engine, string $fallback)
    {
        $name = preg_match('/^(\w+)\((.*)\)$/', $engine, $matches) ? $matches[1] : $engine;
        return $this->client->select(<<<SQL
            SELECT name FROM system.table_engines
            WHERE name = '{$name}'
        SQL)->fetchOne('name') === $name ? $engine : $fallback;
    }

    /**
     * Run the up method
     *
     * @return void
     */
    public function runUp()
    {
        $sql = $this->up();
        if (!empty($sql)) {
            $this->client->write($sql);
        }
    }

    /**
     * Run the down method
     *
     * @return void
     */
    public function runDown()
    {
        $sql = $this->down();
        if (!empty($sql)) {
            $this->client->write($sql);
        }
    }

    /**
     * 獲取 ClickHouse 客戶端實例
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }
}
