<?php

declare(strict_types=1);

namespace Deflinhec\LaravelClickHouse\Database\Eloquent\Concerns;

trait Common
{
    /**
     * Save the model to the database.
     */
    public function save($options = array())
    {
        return static::insert($this->toArray());
    }
}
