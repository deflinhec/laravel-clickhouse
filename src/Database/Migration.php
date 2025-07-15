<?php

namespace Deflinhec\LaravelClickHouse\Database;

use Deflinhec\LaravelClickHouse\Database\Connection;
use Illuminate\Database\Migrations\Migration as BaseMigration;

abstract class Migration extends BaseMigration
{
    /**
     * Get the database connection.
     *
     * @param  string|null  $connection
     * @return \Deflinhec\LaravelClickHouse\Database\Connection
     */
    protected function clickhouse($connection = 'clickhouse'): Connection
    {
        return app('db')->connection($connection);
    }
}
