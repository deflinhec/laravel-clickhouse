<?php

namespace Deflinhec\LaravelClickHouse\Services;

use Illuminate\Support\Facades\Log;
use Deflinhec\LaravelClickHouse\Exceptions\ClickHouseException;

class Service
{
    /**
     * ClickHouse client instance
     *
     * @var \ClickHouseDB\Client
     */
    protected $client;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->client = app(\ClickHouseDB\Client::class);
    }

    /**
     * Test ClickHouse connection
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
     * Execute custom query
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
     * Get ClickHouse client instance
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }
}
