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

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'clickhouse';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'timestamp' => 'datetime',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'message',
        'timestamp',
        'metric',
    ];
}
