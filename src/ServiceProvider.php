<?php

namespace Deflinhec\LaravelClickHouse;

use Illuminate\Support\Facades\Log;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // 註冊 ClickHouse 客戶端
        $this->app->singleton(\ClickHouseDB\Client::class, function ($app) {
            $connection = config('clickhouse.default');
            $config = config("clickhouse.connections.{$connection}");

            // 檢查是否為叢集模式
            if (isset($config['mode']) && $config['mode']) {
                // 叢集模式
                $clusterManager = new Services\ClusterManager($config);
                return $clusterManager->createClient();
            }

            // 單一節點模式
            $client = tap(new \ClickHouseDB\Client([
                'host' => $config['host'],
                'port' => $config['port'],
                'username' => $config['username'],
                'password' => $config['password'],
            ]), function ($client) use ($config) {
                $client
                    ->database($config['database'])
                    ->https($config['options']['ssl'])
                    ->setTimeout($config['options']['timeout'])
                    ->setReadOnlyUser($config['options']['readonly']);
            });

            return $client;
        });

        // 合併配置
        $this->mergeConfigFrom(__DIR__ . '/../config/clickhouse.php', 'clickhouse');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // 註冊 ClickHouse 連接
        $this->registerClickHouseConnection();

        // 註冊 ClickHouse 日誌通道
        $this->registerClickHouseLogging();

        // 發佈配置檔案
        $this->publishes([
            __DIR__ . '/../config/clickhouse.php' => config_path('clickhouse.php'),
        ], 'clickhouse-config');

        // 註冊命令
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\CliCommand::class,
                Console\Commands\MigrateCommand::class,
                Console\Commands\MigrateRollbackCommand::class,
                Console\Commands\MakeMigrationCommand::class,
                Console\Commands\ClusterStatusCommand::class,
            ]);
        }
    }

    /**
     * 註冊 ClickHouse 連接
     *
     * @return void
     */
    protected function registerClickHouseConnection()
    {
        // 檢查 ClickHouse 配置是否存在
        if (!config('clickhouse.connections')) {
            return;
        }

        // 可以在這裡添加 ClickHouse 特定的初始化邏輯
    }

    /**
     * 註冊 ClickHouse 日誌通道
     *
     * @return void
     */
    protected function registerClickHouseLogging()
    {
        if (config('clickhouse.logging.enabled')) {
            // 可以添加 ClickHouse 特定的日誌處理邏輯
        }
    }
}
