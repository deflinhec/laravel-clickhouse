# Laravel Clickhouse

[![Latest Stable Version](https://poser.pugx.org/deflinhec/laravel-clickhouse/v/stable)](https://packagist.org/packages/deflinhec/laravel-clickhouse)
[![License](https://poser.pugx.org/deflinhec/laravel-clickhouse/license)](https://packagist.org/packages/deflinhec/laravel-clickhouse)
[![composer.lock](https://poser.pugx.org/deflinhec/laravel-clickhouse/composerlock)](https://packagist.org/packages/bavix/laravel-clickhouse)

Laravel Clickhouse - Eloquent model for ClickHouse.

* **Vendor**: deflinhec
* **Package**: laravel-clickhouse
* **[Composer](https://getcomposer.org/):** `composer require deflinhec/laravel-clickhouse`

## Credits

This package was originally created by **[bavix](https://github.com/bavix)** and has been enhanced with PHP 7.3 compatibility improvements and comprehensive test coverage.

> [!IMPORTANT]
> I recommend using the standard postgres/mysql interface for clickhouse. More details here: https://clickhouse.com/docs/en/interfaces/mysql

The implementation is provided as is. Further work with the library only through contributors. Added linters, tests and much more. To make it easier for you to send PR.

## PHP Compatibility

This package is now compatible with:
- **PHP 7.3+** (with full type hint removal for backward compatibility)
- **Laravel 5.0+** through **Laravel 12.0+**

## Get started
```sh
$ composer require deflinhec/laravel-clickhouse
```

Then add the code above into your config/app.php file providers section
```php
Deflinhec\LaravelClickHouse\ClickHouseServiceProvider::class,
```

And add new connection into your config/database.php file. Something like this:
```php
'connections' => [
    'clickhouse' => [
        'driver' => 'clickhouse',
        'host' => env('CLICKHOUSE_HOST', 'localhost'),
        'port' => env('CLICKHOUSE_PORT', '8123'),
        'database' => env('CLICKHOUSE_DATABASE', 'default'),
        'username' => env('CLICKHOUSE_USERNAME', 'default'),
        'password' => env('CLICKHOUSE_PASSWORD', ''),
        'options' => [
            'timeout' => 10,
            'protocol' => 'http'
        ]
    ]
]
```

Or like this, if clickhouse runs in cluster
```php
'connections' => [
    'clickhouse' => [
        'driver' => 'clickhouse',
        'servers' => [
            [
                'host' => 'ch-00.domain.com',
                'port' => '8123',
                'database' => 'default',
                'username' => 'default',
                'password' => '',
                'options' => [
                    'timeout' => 10,
                    'protocol' => 'http'
                ]
            ],
            [
                'host' => 'ch-01.domain.com',
                'port' => '8123',
                'database' => 'default',
                'username' => 'default',
                'password' => '',
                'options' => [
                    'timeout' => 10,
                    'protocol' => 'http'
                ]
            ]
        ]
    ]
],
```

Then create model
```php
<?php

use Deflinhec\LaravelClickHouse\Database\Eloquent\Model;

class Payment extends Model
{
    /**
     * @var string
     */
    protected $table = 'payments';
    
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
}
```

And use it
```php
use Tinderbox\ClickhouseBuilder\Query\Column;

Payment::select(
        function (Column $column) {
            return $column->sum('total')
                ->as('sum_total');
        }, 
        'user_id'
    )
    ->whereBetween('paid_at', [
        Carbon\Carbon::parse('2017-01-01'),
        now(),
    ])
    ->groupBy('user_id')
    ->get();
```

## Creating Migrations

You can generate ClickHouse-specific migration files using the included Artisan command:

```bash
# Basic migration
php artisan make:clickhouse-migration create_payment_table
```

The generated migrations will be placed in `database/migrations/clickhouse/` and include ClickHouse-specific features like proper data types, engine configuration, and partitioning strategies.

## Testing

The package includes comprehensive tests that run against PHP 7.3+ and should be compatible with Laravel 5.0+ through Laravel 12.0+, Laravel 5.6 have been tested.

To run the tests:
```bash
# Set up ClickHouse server (if not already running)
docker run -d --name clickhouse-server -p 8124:8123 -p 9001:9000 \
  -e CLICKHOUSE_USER=default -e CLICKHOUSE_PASSWORD=password \
  -e CLICKHOUSE_DB=default clickhouse/clickhouse-server:latest

# Run tests with proper environment variables
CLICKHOUSE_HOST=host.docker.internal CLICKHOUSE_PORT=8124 \
CLICKHOUSE_PASSWORD=password vendor/bin/phpunit
```

## Recent Updates

- **PHP 7.3 Compatibility**: Removed all typed properties, parameter type hints, and return type declarations for backward compatibility
- **Enhanced Testing**: Added comprehensive test suite with proper ClickHouse integration
- **Documentation**: Updated configuration examples and usage instructions
- **Migration Command**: Added `make:clickhouse-migration` command for generating ClickHouse-specific migration files
