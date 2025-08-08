# Laravel ClickHouse Package

[![Latest Stable Version](https://poser.pugx.org/deflinhec/laravel-clickhouse/v/stable)](https://packagist.org/packages/deflinhec/laravel-clickhouse)
[![License](https://poser.pugx.org/deflinhec/laravel-clickhouse/license)](https://packagist.org/packages/deflinhec/laravel-clickhouse)
[![composer.lock](https://poser.pugx.org/deflinhec/laravel-clickhouse/composerlock)](https://packagist.org/packages/deflinhec/laravel-clickhouse)

Laravel ClickHouse integration package based on `smi2/phpclickhouse` client with advanced features including migration system, cluster support, and comprehensive exception handling.

* **Vendor**: deflinhec
* **Package**: laravel-clickhouse
* **[Composer](https://getcomposer.org/):** `composer require deflinhec/laravel-clickhouse`

## Features

- 🔗 ClickHouse connection management with multiple connection support
- 🚀 Migration system with Laravel integration
- 📊 Query builder and custom query execution
- 🧪 Testing tools and CLI interface
- 📝 Complete logging and monitoring
- ⚡ High-performance query support
- 🌐 Cluster mode with load balancing (round-robin, random, failover)
- 🛡️ Comprehensive exception handling with custom error types
- 🔧 Artisan commands for management and testing
- 📦 Composer package with proper autoloading

## PHP Compatibility

This package is compatible with:

* **PHP 7.3+** (with full type hint support)
* **Laravel 5.0+** through **Laravel 12.0+**

## Installation

### 1. Install Package

```bash
composer require deflinhec/laravel-clickhouse
```

### 2. Publish Configuration Files

```bash
php artisan vendor:publish --tag=clickhouse-config
```

### 3. Set Environment Variables

Add the following to your `.env` file:

```env
# ClickHouse Connection Settings
CLICKHOUSE_HOST=localhost
CLICKHOUSE_PORT=8123
CLICKHOUSE_USERNAME=default
CLICKHOUSE_PASSWORD=clickhouse
CLICKHOUSE_DATABASE=default
CLICKHOUSE_SSL=false
CLICKHOUSE_READONLY=true
CLICKHOUSE_TIMEOUT=30

# Logging Settings
CLICKHOUSE_LOGGING_ENABLED=true
CLICKHOUSE_LOGGING_CHANNEL=clickhouse

# Migration Settings
CLICKHOUSE_MIGRATIONS_PATH=database/migrations/clickhouse

# Cluster Mode Settings (Optional)
CLICKHOUSE_CONNECTION=cluster
CLICKHOUSE_CLUSTER_MODE=round_robin
CLICKHOUSE_CLUSTER_NODES=node1,node2
CLICKHOUSE_CLUSTER_PORTS=8123,8123
CLICKHOUSE_CLUSTER_WEIGHTS=1,1
CLICKHOUSE_CLUSTER_RETRY_ATTEMPTS=3
CLICKHOUSE_CLUSTER_RETRY_DELAY=1000
CLICKHOUSE_CLUSTER_HEALTH_CHECK_INTERVAL=30
CLICKHOUSE_CLUSTER_FAILOVER_TIMEOUT=5000
```

## Basic Usage

### Service-based Approach

```php
use Deflinhec\LaravelClickHouse\Services\Service;

$clickHouse = new Service();

// Execute custom query
$result = $clickHouse->executeQuery('SELECT * FROM your_table LIMIT 10');

// Test connection
if ($clickHouse->testConnection()) {
    echo "Connection successful!";
}
```



## Exception Handling

The package provides a custom `ClickHouseException` class with comprehensive error types:

```php
use Deflinhec\LaravelClickHouse\Exceptions\ClickHouseException;

try {
    $result = $clickHouse->executeQuery('SELECT * FROM non_existent_table');
} catch (ClickHouseException $e) {
    echo "Error Type: " . $e->getErrorType();
    echo "Error Code: " . $e->getErrorCode();
    echo "Error Message: " . $e->getMessage();
    echo "Error Context: " . json_encode($e->getContext());
}

// Available error types
ClickHouseException::connectionError($message, $context);
ClickHouseException::queryError($message, $context);
ClickHouseException::configurationError($message, $context);
ClickHouseException::migrationError($message, $context);
ClickHouseException::clusterError($message, $context);
ClickHouseException::authenticationError($message, $context);
ClickHouseException::permissionError($message, $context);
ClickHouseException::timeoutError($message, $context);
ClickHouseException::syntaxError($message, $context);
ClickHouseException::resourceError($message, $context);
```

## Artisan Commands

### Interactive CLI

```bash
# Open ClickHouse interactive CLI
php artisan clickhouse
```

### Connection Testing

```bash
# Basic connection test
php artisan clickhouse:test

# Execute custom query
php artisan clickhouse:test --query="SELECT COUNT(*) FROM your_table"

# Specify connection
php artisan clickhouse:test --connection=local
```

### Cluster Management

```bash
# Check cluster status
php artisan clickhouse:cluster:status

# Detailed cluster status
php artisan clickhouse:cluster:status --detailed

# Check cluster status with specific connection
php artisan clickhouse:cluster:status --connection=cluster
```

### Migration Management

```bash
# Create migration file
php artisan make:clickhouse-migration create_users_table
php artisan make:clickhouse-migration create_orders_table --table=orders
php artisan make:clickhouse-migration create_products_table --create --columns="name:string,price:decimal,is_active:bool"

# Run migrations
php artisan clickhouse:migrate

# Preview migration SQL
php artisan clickhouse:migrate --pretend

# Specify migration path
php artisan clickhouse:migrate --path=database/migrations/clickhouse

# Rollback migrations
php artisan clickhouse:migrate:rollback

# Rollback specific number of migrations
php artisan clickhouse:migrate:rollback --step=3
```

## Migration System

### Important Notes

ClickHouse migrations use Laravel's default `migrations` table to track migration status, rather than creating additional tables in ClickHouse. This ensures migration records are consistent with other Laravel migrations.

### Creating Migration Files

Use the `make:clickhouse-migration` command to quickly create migration files:

```bash
# Basic usage
php artisan make:clickhouse-migration create_users_table

# Specify table name
php artisan make:clickhouse-migration create_orders_table --table=orders

# Create table (explicitly specified)
php artisan make:clickhouse-migration create_products_table --create

# Custom fields
php artisan make:clickhouse-migration create_analytics_table --columns="user_id:int,name:string,score:float,is_active:bool,tags:array"

# Specify path
php artisan make:clickhouse-migration create_test_table --path=database/migrations/custom
```

### Supported Field Types

The command supports the following field type mappings:

- `string` → `String`
- `int` / `integer` → `Int32`
- `bigint` → `Int64`
- `float` → `Float32`
- `double` → `Float64`
- `decimal` → `Decimal(10,2)`
- `bool` / `boolean` → `UInt8`
- `date` → `Date`
- `datetime` / `timestamp` → `DateTime`
- `array` → `Array(String)`
- `json` → `String`

### Manual Migration File Creation

```php
<?php

use Deflinhec\LaravelClickHouse\Database\Migration;

class CreateExampleTable extends Migration
{
    public function up()
    {
        return <<<SQL
            CREATE TABLE IF NOT EXISTS example_table (
                id UInt32,
                name String,
                value Float64,
                is_active UInt8 DEFAULT 1,
                tags Array(String),
                metadata String,
                created_at DateTime DEFAULT now(),
                updated_at DateTime DEFAULT now()
            ) ENGINE = MergeTree()
            ORDER BY (id, created_at)
        SQL;
    }

    public function down()
    {
        return <<<SQL
            DROP TABLE IF EXISTS example_table
        SQL;
    }
}
```

## Configuration

### Connection Settings

```php
'connections' => [
    'default' => [
        'host' => env('CLICKHOUSE_HOST', 'localhost'),
        'port' => env('CLICKHOUSE_PORT', '8123'),
        'username' => env('CLICKHOUSE_USERNAME', 'default'),
        'password' => env('CLICKHOUSE_PASSWORD', ''),
        'database' => env('CLICKHOUSE_DATABASE', 'default'),
        'options' => [
            'timeout' => env('CLICKHOUSE_TIMEOUT', 30),
            'ssl' => env('CLICKHOUSE_SSL', false),
            'verify' => env('CLICKHOUSE_VERIFY', false),
        ],
    ],
    'cluster' => [
        'mode' => env('CLICKHOUSE_CLUSTER_MODE', 'round_robin'),
        'nodes' => [
            'host' => explode(',', env('CLICKHOUSE_CLUSTER_NODES', 'node1,node2')),
            'port' => explode(',', env('CLICKHOUSE_CLUSTER_PORTS', '8123,8123')),
            'weight' => explode(',', env('CLICKHOUSE_CLUSTER_WEIGHTS', '1,1')),
            'username' => env('CLICKHOUSE_USERNAME', 'default'),    
            'password' => env('CLICKHOUSE_PASSWORD', 'clickhouse'),
            'database' => env('CLICKHOUSE_DATABASE', 'default'),
            'options' => [
                'timeout' => env('CLICKHOUSE_TIMEOUT', 30),
                'ssl' => env('CLICKHOUSE_SSL', false),
                'readonly' => env('CLICKHOUSE_READONLY', true),
            ],
        ],
        'options' => [
            'retry_attempts' => env('CLICKHOUSE_CLUSTER_RETRY_ATTEMPTS', 3),
            'retry_delay' => env('CLICKHOUSE_CLUSTER_RETRY_DELAY', 1000), // milliseconds
            'health_check_interval' => env('CLICKHOUSE_CLUSTER_HEALTH_CHECK_INTERVAL', 30), // seconds
            'failover_timeout' => env('CLICKHOUSE_CLUSTER_FAILOVER_TIMEOUT', 5000), // milliseconds
        ],
    ],
],
```



## Table Structure Examples

### Basic Table

Standard ClickHouse table structure example:

```sql
CREATE TABLE example_table (
    id UInt32,
    name String,
    value Float64,
    created_at DateTime DEFAULT now()
) ENGINE = MergeTree()
ORDER BY (id, created_at)
```

### Nested Data Type Example

Using ClickHouse's Nested data type to handle complex hierarchical structures:

```sql
CREATE TABLE nested_example (
    id UInt32,
    depth Nested(identify String, ratio Decimal(8,2), rebate Decimal(8,2)),
    created_at DateTime DEFAULT now()
) ENGINE = MergeTree()
ORDER BY (id, created_at)
```

Benefits include:
- More compact data structure, saving storage space
- More flexible queries (can use `has()`, `arrayJoin()` and other ClickHouse strengths)
- Easier analysis and maintenance of deep structures

## Supported ClickHouse Functions

- `has()` - Check if array contains specific value
- `arrayJoin()` - Expand arrays
- `length()` - Get array length
- `toDate()` - Date conversion
- `row_number() OVER (PARTITION BY ... ORDER BY ...)` - Window functions

## Testing

```bash
# Run tests
php artisan clickhouse:test

# Test specific functionality
php artisan clickhouse:test --query="SELECT has(depth.identify, 'agent1') FROM your_table LIMIT 1"

# Set up ClickHouse server (if not already running)
docker run -d --name clickhouse-server -p 8124:8123 -p 9001:9000 \
  -e CLICKHOUSE_USER=default -e CLICKHOUSE_PASSWORD=password \
  -e CLICKHOUSE_DB=default clickhouse/clickhouse-server:latest

# Run tests with proper environment variables
CLICKHOUSE_HOST=host.docker.internal CLICKHOUSE_PORT=8124 \
CLICKHOUSE_PASSWORD=password vendor/bin/phpunit
```

## Recent Updates

* **Enhanced Migration System**: Full Laravel migration integration with custom commands
* **Cluster Support**: Multi-node ClickHouse cluster with load balancing and health checks
* **Exception Handling**: Comprehensive error handling with custom exception types
* **CLI Interface**: Interactive ClickHouse client and management commands
* **Performance Optimization**: Configurable performance settings and connection pooling
* **Documentation**: Complete documentation with examples and best practices

## Credits

This package was originally created by **bavix** and has been significantly enhanced with modern Laravel features, cluster support, and comprehensive testing.

## License

MIT License
