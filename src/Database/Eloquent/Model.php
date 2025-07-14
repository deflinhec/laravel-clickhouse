<?php

declare(strict_types=1);

namespace Deflinhec\LaravelClickHouse\Database\Eloquent;

use ArrayAccess;
use Deflinhec\LaravelClickHouse\Database\Connection;
use Deflinhec\LaravelClickHouse\Database\Query\Builder as QueryBuilder;
use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Database\Eloquent\Concerns\GuardsAttributes;
use Illuminate\Database\Eloquent\Concerns\HasEvents;
use Illuminate\Database\Eloquent\Concerns\HasRelationships;
use Illuminate\Database\Eloquent\Concerns\HidesAttributes;
use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use JsonSerializable;
use Tinderbox\ClickhouseBuilder\Query\Grammar;

abstract class Model extends \Illuminate\Database\Eloquent\Model 
implements ArrayAccess, UrlRoutable, Arrayable, Jsonable, JsonSerializable
{
    use Concerns\HasAttributes;
    use Concerns\Common;

    /**
     * Indicates if an exception should be thrown when trying to access a missing attribute on a retrieved model.
     *
     * @var bool
     */
    protected static $modelsShouldPreventAccessingMissingAttributes = false;

    /**
     * Handle dynamic method calls into the model.
     *
     * @param string $method
     * @param array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if ($resolver = (static::$relationResolvers[get_class($this)][$method] ?? null)) {
            return $resolver($this);
        }

        return $this->forwardCallTo($this->newQuery(), $method, $parameters);
    }

    /**
     * {@inheritDoc}
     */
    public function newQueryWithoutScopes()
    {
        $builder = $this->newEloquentBuilder($this->newBaseQueryBuilder());

        // Once we have the query builders, we will set the model instances so the
        // builder can easily access any information it may need from the model
        // while it is constructing and executing various queries against it.
        return $builder->setModel($this)
            ->with($this->with);
    }

    /**
     * {@inheritDoc}
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * {@inheritDoc}
     */
    public function newCollection($models = array())
    {
        return new Collection($models);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->resolveRouteBindingQuery($this, $value, $field)
            ->first();
    }

    public function resolveSoftDeletableRouteBinding($value, $field = null)
    {
        return $this->resolveRouteBindingQuery($this, $value, $field)
            ->withTrashed()
            ->first();
    }

    public function resolveChildRouteBinding($childType, $value, $field)
    {
        return $this->resolveChildRouteBindingQuery($childType, $value, $field)
            ->first();
    }

    public function resolveSoftDeletableChildRouteBinding(
        string $childType,
        mixed $value,
        ?string $field = null
    ) {
        return $this->resolveChildRouteBindingQuery($childType, $value, $field)
            ->withTrashed()
            ->first();
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        return $query->where($field ?? $this->getRouteKeyName(), $value);
    }

    public static function preventsAccessingMissingAttributes()
    {
        return static::$modelsShouldPreventAccessingMissingAttributes;
    }

    protected function whenBooted($callback)
    {
    }

    protected function resolveChildRouteBindingQuery(
        string $childType,
        mixed $value,
        ?string $field = null
    ) {
        $relationship = $this->{$this->childRouteBindingRelationshipName($childType)}();

        $field = $field !== null && $field !== '' && $field !== '0' ? $field : $relationship->getRelated()
            ->getRouteKeyName();

        if ($relationship instanceof HasManyThrough ||
            $relationship instanceof BelongsToMany) {
            $field = $relationship->getRelated()
                ->getTable().'.'.$field;
        }

        return $relationship instanceof Model
            ? $relationship->resolveRouteBindingQuery($relationship, $value, $field)
            : $relationship->getRelated()
                ->resolveRouteBindingQuery($relationship, $value, $field);
    }

    protected function childRouteBindingRelationshipName($childType)
    {
        return Str::plural(Str::camel($childType));
    }

    /**
     * Remove the table name from a given key.
     *
     * @param string $key
     * @return string
     */
    protected function removeTableFromKey($key)
    {
        return Str::contains($key, '.') ? last(explode('.', $key)) : $key;
    }

    protected function newBaseQueryBuilder()
    {
        /** @var Connection $connection */
        $connection = $this->getConnection();

        return new QueryBuilder($connection, new Grammar());
    }
}
