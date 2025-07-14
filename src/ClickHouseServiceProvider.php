<?php

declare(strict_types=1);

namespace Deflinhec\LaravelClickHouse;

use Deflinhec\LaravelClickHouse\Database\Connection;
use Deflinhec\LaravelClickHouse\Database\Eloquent\Model;
use Deflinhec\LaravelClickHouse\Database\Query\Pdo;
use Deflinhec\LaravelClickHouse\Database\Query\PdoInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\ServiceProvider;

class ClickHouseServiceProvider extends ServiceProvider
{
    /**
     * @throws
     */
    public function boot(): void
    {
        Model::setConnectionResolver($this->app['db']);
        Model::setEventDispatcher($this->app['events']);
    }

    public function register(): void
    {
        $this->app->singleton(PdoInterface::class, Pdo::class);
        $this->app->resolving('db', static function (DatabaseManager $db) {
            $db->extend('clickhouse', static function ($config, $name) {
                return new Connection(\array_merge($config, [
                    'name' => $name,
                ]));
            });
        });
    }
}
