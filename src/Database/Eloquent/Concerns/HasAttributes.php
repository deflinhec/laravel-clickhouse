<?php

declare(strict_types=1);

namespace Deflinhec\LaravelClickHouse\Database\Eloquent\Concerns;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Deflinhec\LaravelClickHouse\Database\Connection;
use Illuminate\Database\Eloquent\Concerns\HasAttributes as BaseHasAttributes;
use Illuminate\Support\Facades\Date;

trait HasAttributes
{
    use BaseHasAttributes;

    public function getCasts(): array
    {
        $connection = $this->getConnection();

        if ($connection instanceof Connection) {
            return $this->casts ?? [];
        }

        if ($this->getIncrementing()) {
            return array_merge([$this->getKeyName() => $this->getKeyType()], $this->casts);
        }

        return $this->casts ?? [];
    }

    public function getDateFormat(): string
    {
        $connection = $this->getConnection();

        if ($connection instanceof Connection) {
            return $this->dateFormat ?? 'Y-m-d H:i:s';
        }

        return $this->dateFormat ?: $this->getConnection()
            ->getQueryGrammar()->getDateFormat();
    }

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return Carbon::instance($date)->toDateTimeString();
    }
}
