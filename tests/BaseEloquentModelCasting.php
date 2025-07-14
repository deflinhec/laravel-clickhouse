<?php

declare(strict_types=1);

namespace Deflinhec\LaravelClickHouse\Tests;

use Deflinhec\LaravelClickHouse\Database\Eloquent\Model;

class BaseEloquentModelCasting extends Model
{
    use Helpers;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'test_table';

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
        'paid_at' => 'datetime',
        'intAttribute' => 'int',
        'floatAttribute' => 'float',
        'stringAttribute' => 'string',
        'boolAttribute' => 'bool',
        'booleanAttribute' => 'boolean',
        'objectAttribute' => 'object',
        'arrayAttribute' => 'array',
        'jsonAttribute' => 'json',
        'dateAttribute' => 'date',
        'datetimeAttribute' => 'datetime',
        'timestampAttribute' => 'timestamp',
    ];

    public function jsonAttributeValue()
    {
        return $this->attributes['jsonAttribute'];
    }
}
