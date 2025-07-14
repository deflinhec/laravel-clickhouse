<?php

declare(strict_types=1);

namespace Deflinhec\LaravelClickHouse\Tests;

use Deflinhec\LaravelClickHouse\Database\Eloquent\Model;

class FirstTableEntry extends Model
{
    /**
     * @var string
     */
    protected $table = 'my_first_table';

    protected $fillable = ['user_id', 'message', 'timestamp', 'metric'];
}
