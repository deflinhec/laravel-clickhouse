<?php

declare(strict_types=1);

namespace Deflinhec\LaravelClickHouse\Database\Query;

final class Pdo implements PdoInterface
{
    public function quote($binding)
    {
        return $binding;
    }
}
