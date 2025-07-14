<?php

declare(strict_types=1);

namespace Deflinhec\LaravelClickHouse\Tests;

use Deflinhec\LaravelClickHouse\Database\Eloquent\Builder;
use Deflinhec\LaravelClickHouse\Database\Eloquent\Model;
use Mockery\MockInterface;

class BaseEloquentModelWith extends Model
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
     * @return Builder|MockInterface
     */
    public function newQuery(): Builder
    {
        /** @var MockInterface|Builder $builder */
        $builder = $this->mock(Builder::class);
        $builder->shouldReceive('with')
            ->once()
            ->with(['foo', 'bar'])
            ->andReturn('foo');

        return $builder;
    }
}
