<?php

declare(strict_types=1);

namespace Deflinhec\LaravelClickHouse;

use Deflinhec\LaravelClickHouse\Console\MigrateMakeCommand;
use Deflinhec\LaravelClickHouse\Database\Connection;
use Deflinhec\LaravelClickHouse\Database\Eloquent\Model;
use Deflinhec\LaravelClickHouse\Database\Query\Pdo;
use Deflinhec\LaravelClickHouse\Database\Query\PdoInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\ServiceProvider;

class ClickHouseServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->singleton(PdoInterface::class, Pdo::class);

        $this->registerDatabase();

        $this->registerCommands();

        $this->registerMigrations();

        $this->registerMigrations();
    }

    private function registerDatabase(): void
    {
        $this->app->resolving(
            'db',
            static function (DatabaseManager $db) {
                $db->extend(
                    'clickhouse',
                    static function ($config, $name) {
                        return new Connection(\array_merge($config, [
                            'name' => $name,
                        ]));
                    }
                );
            }
        );
    }

    private function registerCommands(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            MigrateMakeCommand::class,
        ]);
    }

    private function registerMigrations(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->loadMigrationsFrom(
            $this->app->databasePath('migrations/clickhouse')
        );
    }
}
