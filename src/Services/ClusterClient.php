<?php

namespace Deflinhec\LaravelClickHouse\Services;

use Illuminate\Support\Facades\Log;
use Deflinhec\LaravelClickHouse\Exceptions\ClickHouseException;

class ClusterClient
{
    /**
     * Cluster manager
     *
     * @var ClusterManager
     */
    protected $clusterManager;

    /**
     * Constructor
     *
     * @param ClusterManager $clusterManager
     */
    public function __construct(ClusterManager $clusterManager)
    {
        $this->clusterManager = $clusterManager;
    }

    /**
     * Execute query
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

                // Wait before retry
                usleep($retryDelay * 1000);
            }
        }
    }

    /**
     * Execute write operation
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

                // Wait before retry
                usleep($retryDelay * 1000);
            }
        }
    }

    /**
     * Create client from node configuration
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
     * Get cluster status
     *
     * @return array
     */
    public function getClusterStatus()
    {
        return $this->clusterManager->getClusterStatus();
    }

    /**
     * Test connection
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
