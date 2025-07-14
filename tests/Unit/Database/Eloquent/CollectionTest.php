<?php

declare(strict_types=1);

namespace Deflinhec\LaravelClickHouse\Tests\Unit\Database\Eloquent;

use Deflinhec\LaravelClickHouse\Database\Connection;
use Deflinhec\LaravelClickHouse\Database\Eloquent\Collection;
use Deflinhec\LaravelClickHouse\Database\Query\Builder;
use Deflinhec\LaravelClickHouse\Tests\BaseEloquentModelCasting;
use Deflinhec\LaravelClickHouse\Tests\Helpers;
use Carbon\Carbon;
use Illuminate\Database\DatabaseManager;
use Mockery\Mock;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

/**
 * @property Mock|Connection connection
 */
class CollectionTest extends TestCase
{
    use Helpers;

    /**
     * @var MockInterface&Connection
     */
    private $connection;

    private $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->mock(Connection::class);

        $this->connection
            ->shouldReceive('getName')
            ->andReturn((new BaseEloquentModelCasting())->getConnectionName());

        $this->connection
            ->shouldReceive('getDriverName')
            ->andReturn('clickhouse');

        /** @var Mock|DatabaseManager $resolver */
        $resolver = $this->mock(DatabaseManager::class);
        $resolver->shouldReceive('connection')
            ->andReturn($this->connection);

        BaseEloquentModelCasting::setConnectionResolver($resolver);
    }

    public function testMapModelToModel(): void
    {
        $connectionResult = collect()
            ->times(5, function (int $id) {
                return [
                    'id' => $id,
                ];
            });

        $this->connection
            ->shouldReceive('select')
            ->andReturn($connectionResult->toArray());

        $now = now();

        $models = BaseEloquentModelCasting::all()
            ->map(function (BaseEloquentModelCasting $model) use ($now) {
                $model->datetimeAttribute = $now;

                return $model;
            });

        self::assertInstanceOf(Collection::class, $models);
        self::assertCount($connectionResult->count(), $models);

        $models->each(function (BaseEloquentModelCasting $model, int $key) use ($now) {
            self::assertSame($key + 1, $model->id);
            self::assertInstanceOf(Carbon::class, $model->datetimeAttribute);
            self::assertSame($now->toDateTimeString(), $model->datetimeAttribute->toDateTimeString());
        });
    }

    public function testMapModelToArray(): void
    {
        $connectionResult = collect()
            ->times(5, function (int $id) {
                return [
                    'id' => $id,
                ];
            });

        $this->connection
            ->shouldReceive('select')
            ->andReturn($connectionResult->toArray());

        $now = now();

        $collection = BaseEloquentModelCasting::all()
            ->map(function (BaseEloquentModelCasting $model) use ($now) {
                return [
                    'id' => $model->id,
                    'datetimeAttribute' => $now,
                ];
            });

        self::assertInstanceOf(\Illuminate\Support\Collection::class, $collection);
        self::assertCount($connectionResult->count(), $collection);

        $collection->each(function (array $row, int $key) use ($now) {
            self::assertSame($key + 1, $row['id']);
            self::assertInstanceOf(Carbon::class, $row['datetimeAttribute']);
            self::assertSame($now->toDateTimeString(), $row['datetimeAttribute']->toDateTimeString());
        });
    }

    /**
     * @dataProvider findDataProvider
     */
    public function testFind($key): void
    {
        $connectionResult = collect()
            ->times(5, function (int $id) {
                return [
                    'id' => $id,
                ];
            });

        $this->connection
            ->shouldReceive('select')
            ->andReturn($connectionResult->toArray());

        $found = BaseEloquentModelCasting::all()->find($key = value($key));

        if (is_array($key)) {
            self::assertInstanceOf(Collection::class, $found);
            self::assertCount(count($key), $found);
        } else {
            self::assertInstanceOf(BaseEloquentModelCasting::class, $found);
        }
    }

    /**
     * @dataProvider containsDataProvider
     */
    public function testContains(bool $expected, $key, $operator = null, $value = null): void
    {
        $connectionResult = collect()
            ->times(5, function (int $id) {
                return [
                    'id' => $id,
                ];
            });

        $this->connection
            ->shouldReceive('select')
            ->andReturn($connectionResult->toArray());

        if ($operator !== null && $value !== null) {
            $contains = BaseEloquentModelCasting::all()->contains($key, $operator, $value);
        } else {
            $contains = BaseEloquentModelCasting::all()->contains($key);
        }

        self::assertSame($expected, $contains);
    }

    public function testGet(): void
    {
        $connectionResult = collect()
            ->times(5, function (int $id) {
                return [
                    'id' => $id,
                    'floatAttribute' => (string) $this->faker()
                        ->randomFloat(2),
                ];
            });

        $this->connection
            ->shouldReceive('select')
            ->andReturn($connectionResult->toArray());

        $models = BaseEloquentModelCasting::all();

        self::assertInstanceOf(Collection::class, $models);
        self::assertCount($connectionResult->count(), $models);
        $models = $models->map(function (BaseEloquentModelCasting $model) {
            return $model->toArray();
        });

        self::assertSame(
            $connectionResult
                ->map(function (array $row) {
                    $row['floatAttribute'] = (float) $row['floatAttribute'];

                    return $row;
                })
                ->toArray(),
            $models->toArray()
        );
    }

    public static function findDataProvider(): array
    {
        return [
            [5],
            [function () {
                return tap(new BaseEloquentModelCasting(), function($model) {
                    $model->id = 5;
                });
            }, ],
            [1, 5],
        ];
    }

    public static function containsDataProvider()
    {
        return [[true, 5], [false, 6], [true, 'id', '>=', 5], [false, 'id', '>=', 6]];
    }
}
