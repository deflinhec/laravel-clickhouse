<?php

declare(strict_types=1);

namespace Deflinhec\LaravelClickHouse\Database\Query;

interface PdoInterface
{
    public function quote($binding);
}
