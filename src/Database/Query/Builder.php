<?php

declare(strict_types=1);

namespace Deflinhec\LaravelClickHouse\Database\Query;
use Deflinhec\LaravelClickHouse\Database\Connection;
use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Macroable;
use Tinderbox\ClickhouseBuilder\Query\BaseBuilder;
use Tinderbox\ClickhouseBuilder\Query\Expression;

class Builder extends BaseBuilder
{
    use Macroable {
        __call as macroCall;
    }

    protected $connection;

    public function __construct($connection, $grammar)
    {
        $this->connection = $connection;
        $this->grammar = $grammar;
    }

    /**
     * Perform compiled from builder sql query and getting result.
     *
     * @throws \Tinderbox\Clickhouse\Exceptions\ClientException
     */
    public function get()
    {
        if ($this->async !== []) {
            $result = $this->connection->selectAsync($this->toAsyncSqls());
        } else {
            $result = $this->connection->select($this->toSql(), [], $this->getFiles());
        }

        return collect($result);
    }

    /**
     * Performs compiled sql for count rows only. May be used for pagination
     * Works only without async queries.
     *
     * @param string $column Column to pass into count() aggregate function
     *
     * @throws \Tinderbox\Clickhouse\Exceptions\ClientException
     */
    public function count($column = '*')
    {
        $builder = $this->getCountQuery();
        $result = $builder->get();

        if (count($this->groups) > 0) {
            return count($result);
        }

        return (int) ($result[0]['count'] ?? 0);
    }

    /**
     * Perform query and get first row.
     *
     * @return mixed|null
     *
     * @throws \Tinderbox\Clickhouse\Exceptions\ClientException
     */
    public function first()
    {
        return $this->get()
            ->first();
    }

    /**
     * Makes clean instance of builder.
     */
    public function newQuery()
    {
        return new static($this->connection, $this->grammar);
    }

    /**
     * Insert in table data from files.
     *
     * @throws \Tinderbox\Clickhouse\Exceptions\ClientException
     */
    public function insertFiles($columns, $files, $format = 'CSV', $concurrency = 5)
    {
        return $this->connection->insertFiles(
            (string) $this->getFrom()
                ->getTable(),
            $columns,
            $files,
            $format,
            $concurrency
        );
    }

    /**
     * Performs insert query.
     */
    public function insert($values)
    {
        if ($values === []) {
            return false;
        }

        if (! is_array(reset($values))) {
            $values = [$values];
        }

        // Here, we will sort the insert keys for every record so that each insert is
        // in the same order for the record. We need to make sure this is the case
        // so there are not any errors or problems when inserting these records.
        foreach ($values as &$value) {
            ksort($value);
        }

        return $this->connection->insert($this->grammar->compileInsert($this, $values), Arr::flatten($values));
    }

    public function getCountForPagination()
    {
        return (int) $this->getConnection()
            ->table(
                $this
                    ->cloneWithout([
                        'columns' => [],
                        'orders' => [],
                        'limit' => null,
                    ])
                    ->select(new Expression('1')), null
            )
            ->count();
    }

    /**
     * Set the limit and offset for a given page.
     *
     * @param  int  $page
     * @param  int  $perPage
     * @return $this
     */
    public function forPage($page, $perPage = 15)
    {
        return $this->limit($perPage, ($page - 1) * $perPage);
    }

    public function getConnection()
    {
        return $this->connection;
    }
}
