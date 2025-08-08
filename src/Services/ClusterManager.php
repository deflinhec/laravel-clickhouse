<?php

namespace Deflinhec\LaravelClickHouse\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Deflinhec\LaravelClickHouse\Exceptions\ClickHouseException;

class ClusterManager
{
    /**
     * Cluster configuration
     *
     * @var array
     */
    protected $config;

    /**
     * Available nodes list
     *
     * @var array
     */
    protected $availableNodes = [];

    /**
     * Current node index
     *
     * @var int
     */
    protected $currentNodeIndex = 0;

    /**
     * Node health status cache key
     *
     * @var string
     */
    protected $healthCacheKey = 'clickhouse_cluster_health';

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        foreach ($this->config['nodes']['host'] as $index => $host) {
            $this->availableNodes[] = [
                'host' => $host,
                'port' => $this->config['nodes']['port'][$index],
                'username' => $this->config['nodes']['username'],
                'password' => $this->config['nodes']['password'],
                'database' => $this->config['nodes']['database'],
                'weight' => $this->config['nodes']['weight'][$index],
                'options' => $this->config['nodes']['options'],
            ];
        }
        $this->updateNodeHealth();
    }

    /**
     * Get next available node
     *
     * @return array|null
     */
    public function getNextNode()
    {
        $this->updateNodeHealth();

        $healthyNodes = $this->getHealthyNodes();

        if (empty($healthyNodes)) {
            throw ClickHouseException::clusterError(
                'No healthy ClickHouse nodes available',
                ['available_nodes' => count($this->availableNodes)]
            );
        }

        switch ($this->config['mode']) {
            case 'round_robin':
                return $this->getRoundRobinNode($healthyNodes);
            case 'random':
                return $this->getRandomNode($healthyNodes);
            case 'failover':
                return $this->getFailoverNode($healthyNodes);
            default:
                return $this->getRoundRobinNode($healthyNodes);
        }
    }

    /**
     * Get node using round-robin mode
     *
     * @param array $healthyNodes
     * @return array
     */
    protected function getRoundRobinNode(array $healthyNodes)
    {
        $node = $healthyNodes[$this->currentNodeIndex % count($healthyNodes)];
        $this->currentNodeIndex++;
        return $node;
    }

    /**
     * Get node using random mode
     *
     * @param array $healthyNodes
     * @return array
     */
    protected function getRandomNode(array $healthyNodes)
    {
        $weights = array_column($healthyNodes, 'weight');
        $totalWeight = array_sum($weights);
        $random = mt_rand(1, $totalWeight);

        $currentWeight = 0;
        foreach ($healthyNodes as $index => $node) {
            $currentWeight += $node['weight'];
            if ($random <= $currentWeight) {
                return $node;
            }
        }

        return $healthyNodes[0];
    }

    /**
     * Get node using failover mode
     *
     * @param array $healthyNodes
     * @return array
     */
    protected function getFailoverNode(array $healthyNodes)
    {
        return $healthyNodes[0];
    }

    /**
     * Get healthy nodes
     *
     * @return array
     */
    protected function getHealthyNodes()
    {
        $healthStatus = Cache::get($this->healthCacheKey, []);
        $healthyNodes = [];

        foreach ($this->availableNodes as $index => $node) {
            if (!isset($healthStatus[$index]) || $healthStatus[$index]['status'] === 'healthy') {
                $healthyNodes[] = $node;
            }
        }

        return $healthyNodes;
    }

    /**
     * Update node health status
     *
     * @return void
     */
    protected function updateNodeHealth()
    {
        $lastCheck = Cache::get($this->healthCacheKey . '_last_check', 0);
        $interval = $this->config['options']['health_check_interval'] ?? 30;

        if (time() - $lastCheck < $interval) {
            return;
        }

        $healthStatus = [];

        foreach ($this->availableNodes as $index => $node) {
            $healthStatus[$index] = $this->checkNodeHealth($node);
        }

        Cache::put($this->healthCacheKey, $healthStatus, $interval * 2);
        Cache::put($this->healthCacheKey . '_last_check', time(), $interval * 2);
    }

    /**
     * Check node health status
     *
     * @param array $node
     * @return array
     */
    protected function checkNodeHealth(array $node)
    {
        try {
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

            $result = $client->select('SELECT 1 as health_check');

            return [
                'status' => 'healthy',
                'last_check' => time(),
                'response_time' => microtime(true),
            ];
        } catch (\Exception $e) {
            Log::warning("ClickHouse node health check failed", [
                'node' => $node['host'] . ':' . $node['port'],
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'unhealthy',
                'last_check' => time(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create cluster client
     *
     * @return ClickHouseClusterClient
     */
    public function createClient()
    {
        return new ClusterClient($this);
    }

    /**
     * Get cluster status
     *
     * @return array
     */
    public function getClusterStatus()
    {
        $this->updateNodeHealth();
        $healthStatus = Cache::get($this->healthCacheKey, []);

        $status = [
            'mode' => $this->config['mode'],
            'total_nodes' => count($this->availableNodes),
            'healthy_nodes' => 0,
            'unhealthy_nodes' => 0,
            'nodes' => [],
        ];

        foreach ($this->availableNodes as $index => $node) {
            $nodeStatus = $healthStatus[$index] ?? ['status' => 'unknown'];
            $status['nodes'][] = [
                'host' => $node['host'],
                'port' => $node['port'],
                'status' => $nodeStatus['status'],
                'last_check' => $nodeStatus['last_check'] ?? null,
                'error' => $nodeStatus['error'] ?? null,
            ];

            if ($nodeStatus['status'] === 'healthy') {
                $status['healthy_nodes']++;
            } else {
                $status['unhealthy_nodes']++;
            }
        }

        return $status;
    }
}
