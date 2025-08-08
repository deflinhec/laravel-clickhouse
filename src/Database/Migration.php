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
     * 執行 up 方法
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
     * 執行 down 方法
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
