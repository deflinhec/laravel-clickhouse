<?php

declare(strict_types=1);

namespace Deflinhec\LaravelClickHouse\Database;

use Deflinhec\LaravelClickHouse\Database\Query\Builder;
use Deflinhec\LaravelClickHouse\Database\Query\PdoInterface;
use Tinderbox\ClickhouseBuilder\Query\Grammar;

class Connection extends \Tinderbox\ClickhouseBuilder\Integrations\Laravel\Connection
{
    public function query(): Builder
    {
        return new Builder($this, new Grammar());
    }

    public function getPdo(): PdoInterface
    {
        return app(PdoInterface::class);
    }
}
