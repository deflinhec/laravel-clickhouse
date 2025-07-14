<?php

declare(strict_types=1);

namespace Deflinhec\LaravelClickHouse\Tests;

use Deflinhec\LaravelClickHouse\Database\Eloquent\Model;

/**
 * @property int id
 * @property array transactions
 * @property int payment_system_id
 * @property float amount
 * @property string status
 */
class BaseEloquentModel extends Model
{
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
        'timestamp' => 'datetime',
    ];

    public function getListItemsAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setListItemsAttribute($value)
    {
        $this->attributes['list_items'] = json_encode($value);
    }

    public function getPasswordAttribute()
    {
        return '******';
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password_hash'] = sha1($value);
    }
}
