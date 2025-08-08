<?php

namespace Deflinhec\LaravelClickHouse\Services;

use Illuminate\Support\Facades\Log;
use Deflinhec\LaravelClickHouse\Exceptions\ClickHouseException;

class ClusterClient
{
    /**
     * 叢集管理器
     *
     * @var ClusterManager
     */
    protected $clusterManager;

    /**
     * 建構函數
     *
     * @param ClusterManager $clusterManager
     */
    public function __construct(ClusterManager $clusterManager)
    {
        $this->clusterManager = $clusterManager;
    }

    /**
     * 執行查詢
     *
     * @param string $sql
     * @param array $params
     * @return mixed
     */
    public function select($sql, $params = [])
    {
        $attempts = 0;
        $maxAttempts = $this->clusterManager->config['options']['retry_attempts'] ?? 3;
        $retryDelay = $this->clusterManager->config['options']['retry_delay'] ?? 1000;

        while ($attempts < $maxAttempts) {
            try {
                $node = $this->clusterManager->getNextNode();
                $client = $this->createClientFromNode($node);

                Log::info("Executing query on ClickHouse node", [
                    'node' => $node['host'] . ':' . $node['port'],
                    'sql' => $sql,
                    'attempt' => $attempts + 1,
                ]);

                return $client->select($sql, $params);
            } catch (\Exception $e) {
                $attempts++;

                Log::warning("ClickHouse query failed", [
                    'node' => $node['host'] . ':' . $node['port'],
                    'sql' => $sql,
                    'error' => $e->getMessage(),
                    'attempt' => $attempts,
                    'max_attempts' => $maxAttempts,
                ]);

                if ($attempts >= $maxAttempts) {
                    throw ClickHouseException::queryError(
                        "ClickHouse query failed after {$maxAttempts} attempts: " . $e->getMessage(),
                        [
                            'sql' => $sql,
                            'params' => $params,
                            'attempts' => $attempts,
                            'max_attempts' => $maxAttempts,
                            'exception' => $e->getMessage()
                        ]
                    );
                }

                // 等待重試
                usleep($retryDelay * 1000);
            }
        }
    }

    /**
     * 執行寫入操作
     *
     * @param string $sql
     * @param array $params
     * @return mixed
     */
    public function write($sql, $params = [])
    {
        $attempts = 0;
        $maxAttempts = $this->clusterManager->config['options']['retry_attempts'] ?? 3;
        $retryDelay = $this->clusterManager->config['options']['retry_delay'] ?? 1000;

        while ($attempts < $maxAttempts) {
            try {
                $node = $this->clusterManager->getNextNode();
                $client = $this->createClientFromNode($node);

                Log::info("Executing write operation on ClickHouse node", [
                    'node' => $node['host'] . ':' . $node['port'],
                    'sql' => $sql,
                    'attempt' => $attempts + 1,
                ]);

                return $client->write($sql, $params);
            } catch (\Exception $e) {
                $attempts++;

                Log::warning("ClickHouse write operation failed", [
                    'node' => $node['host'] . ':' . $node['port'],
                    'sql' => $sql,
                    'error' => $e->getMessage(),
                    'attempt' => $attempts,
                    'max_attempts' => $maxAttempts,
                ]);

                if ($attempts >= $maxAttempts) {
                    throw ClickHouseException::queryError(
                        "ClickHouse write operation failed after {$maxAttempts} attempts: " . $e->getMessage(),
                        [
                            'sql' => $sql,
                            'params' => $params,
                            'attempts' => $attempts,
                            'max_attempts' => $maxAttempts,
                            'exception' => $e->getMessage()
                        ]
                    );
                }

                // 等待重試
                usleep($retryDelay * 1000);
            }
        }
    }

    /**
     * 從節點配置創建客戶端
     *
     * @param array $node
     * @return Client
     */
    protected function createClientFromNode(array $node)
    {
        $client = new \ClickHouseDB\Client([
            'host' => $node['host'],
            'port' => $node['port'],
            'username' => $node['username'],
            'password' => $node['password'],
        ]);

        $client->database($node['database']);

        if ($node['options']['ssl'] ?? false) {
            $client->https(true);
        }

        $client->setTimeout($node['options']['timeout'] ?? 30);

        if ($node['options']['readonly'] ?? false) {
            $client->setReadOnlyUser(true);
        }

        return $client;
    }

    /**
     * 獲取叢集狀態
     *
     * @return array
     */
    public function getClusterStatus()
    {
        return $this->clusterManager->getClusterStatus();
    }

    /**
     * 測試連接
     *
     * @return bool
     */
    public function testConnection()
    {
        try {
            $this->select('SELECT 1 as test');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
