<?php

namespace Deflinhec\LaravelClickHouse\Services;

use Illuminate\Support\Facades\Log;
use Deflinhec\LaravelClickHouse\Exceptions\ClickHouseException;

class Service
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
     * 測試 ClickHouse 連接
     *
     * @return bool
     */
    public function testConnection()
    {
        try {
            $result = $this->client->select('SELECT 1 as test');
            return $result->fetchOne('test') == 1;
        } catch (\Exception $e) {
            Log::error('ClickHouse connection test failed: ' . $e->getMessage());
            throw ClickHouseException::connectionError(
                'ClickHouse connection test failed: ' . $e->getMessage(),
                ['exception' => $e->getMessage()]
            );
        }
    }

    /**
     * 執行自定義查詢
     *
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function executeQuery($sql, $params = [])
    {
        try {
            $result = $this->client->select($sql, $params);
            return $result->rows();
        } catch (\Exception $e) {
            Log::error('ClickHouse query failed: ' . $e->getMessage(), [
                'sql' => $sql,
                'params' => $params
            ]);
            throw ClickHouseException::queryError(
                'ClickHouse query failed: ' . $e->getMessage(),
                [
                    'sql' => $sql,
                    'params' => $params,
                    'exception' => $e->getMessage()
                ]
            );
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
