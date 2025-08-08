<?php

namespace Deflinhec\LaravelClickHouse\Console\Commands;

use Illuminate\Console\Command;
use Deflinhec\LaravelClickHouse\Services\ClusterManager;
use Deflinhec\LaravelClickHouse\Exceptions\ClickHouseException;

class ClusterStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clickhouse:cluster:status 
                            {--connection= : The connection to use}
                            {--detailed : Show detailed node information}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check ClickHouse cluster status';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $connection = $this->option('connection') ?: config('clickhouse.default');
        $config = config("clickhouse.connections.{$connection}");

        if (!isset($config['mode']) || !$config['mode']) {
            $this->error("❌ Connection '{$connection}' is not configured for cluster mode");
            return 1;
        }

        try {
            $clusterManager = new ClusterManager($config);
            $status = $clusterManager->getClusterStatus();

            $this->displayClusterStatus($status);
            return 0;
        } catch (ClickHouseException $e) {
            $this->error("❌ ClickHouse Cluster Error ({$e->getErrorType()}): " . $e->getMessage());
            if ($this->output->isVerbose()) {
                $this->line("Error Code: {$e->getErrorCode()}");
                $this->line("Context: " . json_encode($e->getContext()));
            }
            return 1;
        } catch (\Exception $e) {
            $this->error("❌ Failed to get cluster status: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Display cluster status.
     *
     * @param array $status
     * @return void
     */
    protected function displayClusterStatus(array $status)
    {
        $this->info('🔍 ClickHouse Cluster Status');
        $this->line('');

        // 基本資訊
        $this->info('📊 Cluster Information:');
        $this->table(['Property', 'Value'], [
            ['Mode', $status['mode']],
            ['Total Nodes', $status['total_nodes']],
            ['Healthy Nodes', $status['healthy_nodes']],
            ['Unhealthy Nodes', $status['unhealthy_nodes']],
        ]);

        $this->line('');

        // 節點詳細資訊
        if ($this->option('detailed')) {
            $this->info('🖥️  Node Details:');
            $nodeRows = [];

            foreach ($status['nodes'] as $index => $node) {
                $statusIcon = $node['status'] === 'healthy' ? '✅' : '❌';
                $lastCheck = $node['last_check'] ? date('Y-m-d H:i:s', $node['last_check']) : 'Never';

                $nodeRows[] = [
                    $index + 1,
                    $node['host'] . ':' . $node['port'],
                    $statusIcon . ' ' . ucfirst($node['status']),
                    $lastCheck,
                    $node['error'] ?? '-',
                ];
            }

            $this->table(['#', 'Node', 'Status', 'Last Check', 'Error'], $nodeRows);
        } else {
            $this->info('🖥️  Node Summary:');
            foreach ($status['nodes'] as $index => $node) {
                $statusIcon = $node['status'] === 'healthy' ? '✅' : '❌';
                $this->line("  {$statusIcon} Node " . ($index + 1) . ": {$node['host']}:{$node['port']} ({$node['status']})");
            }
        }

        $this->line('');

        // 健康狀態總結
        if ($status['healthy_nodes'] === $status['total_nodes']) {
            $this->info('🎉 All nodes are healthy!');
        } elseif ($status['healthy_nodes'] > 0) {
            $this->warn('⚠️  Some nodes are unhealthy, but cluster is operational');
        } else {
            $this->error('💥 All nodes are unhealthy!');
        }
    }
}
