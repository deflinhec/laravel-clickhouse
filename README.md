# Laravel ClickHouse Package

Laravel ClickHouse 整合套件，基於 `smi2/phpclickhouse` 客戶端。

## 功能特色

- 🔗 ClickHouse 連接管理
- 🚀 遷移系統支援
- 📊 查詢建構器
- 🧪 測試工具
- 📝 完整的日誌記錄
- ⚡ 高效能查詢支援

## 安裝

### 1. 安裝依賴

```bash
composer require smi2/phpclickhouse
```

### 2. 發佈配置檔案

```bash
php artisan vendor:publish --tag=clickhouse-config
```

### 3. 設定環境變數

在 `.env` 檔案中添加：

```env
# ClickHouse 連接設定
CLICKHOUSE_HOST=localhost
CLICKHOUSE_PORT=8123
CLICKHOUSE_USERNAME=default
CLICKHOUSE_PASSWORD=clickhouse
CLICKHOUSE_DATABASE=default
CLICKHOUSE_SSL=false
CLICKHOUSE_READONLY=true
CLICKHOUSE_TIMEOUT=30

# 日誌設定
CLICKHOUSE_LOGGING_ENABLED=true
CLICKHOUSE_LOGGING_CHANNEL=clickhouse

# 遷移設定
CLICKHOUSE_MIGRATIONS_PATH=database/migrations/clickhouse

# 叢集模式設定（可選）
CLICKHOUSE_CONNECTION=cluster
CLICKHOUSE_CLUSTER_MODE=round_robin
CLICKHOUSE_CLUSTER_NODE1_HOST=clickhouse-node1
CLICKHOUSE_CLUSTER_NODE1_PORT=8123
CLICKHOUSE_CLUSTER_NODE1_USERNAME=default
CLICKHOUSE_CLUSTER_NODE1_PASSWORD=clickhouse
CLICKHOUSE_CLUSTER_NODE1_DATABASE=default
CLICKHOUSE_CLUSTER_NODE1_WEIGHT=1
CLICKHOUSE_CLUSTER_NODE2_HOST=clickhouse-node2
CLICKHOUSE_CLUSTER_NODE2_PORT=8123
CLICKHOUSE_CLUSTER_NODE2_USERNAME=default
CLICKHOUSE_CLUSTER_NODE2_PASSWORD=clickhouse
CLICKHOUSE_CLUSTER_NODE2_DATABASE=default
CLICKHOUSE_CLUSTER_NODE2_WEIGHT=1
CLICKHOUSE_CLUSTER_RETRY_ATTEMPTS=3
CLICKHOUSE_CLUSTER_RETRY_DELAY=1000
CLICKHOUSE_CLUSTER_HEALTH_CHECK_INTERVAL=30
CLICKHOUSE_CLUSTER_FAILOVER_TIMEOUT=5000
```

## 使用方法

### 基本查詢

```php
use Deflinhec\LaravelClickHouse\Services\Service;

$clickHouse = new Service();

// 執行自定義查詢
$result = $clickHouse->executeQuery('SELECT * FROM your_table LIMIT 10');

// 測試連接
if ($clickHouse->testConnection()) {
    echo "連接成功！";
}
```

### 異常處理

套件提供了自定義的 `ClickHouseException` 類別，包含以下錯誤類型：

```php
use Deflinhec\LaravelClickHouse\Exceptions\ClickHouseException;

try {
    $result = $clickHouse->executeQuery('SELECT * FROM non_existent_table');
} catch (ClickHouseException $e) {
    echo "錯誤類型: " . $e->getErrorType();
    echo "錯誤代碼: " . $e->getErrorCode();
    echo "錯誤訊息: " . $e->getMessage();
    echo "錯誤上下文: " . json_encode($e->getContext());
}

// 可用的錯誤類型
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

## Artisan 命令

### 測試連接

```bash
# 基本連接測試
php artisan clickhouse:test

# 執行自定義查詢
php artisan clickhouse:test --query="SELECT COUNT(*) FROM your_table"

# 指定連接
php artisan clickhouse:test --connection=local
```

### 叢集管理

```bash
# 檢查叢集狀態
php artisan clickhouse:cluster:status

# 詳細叢集狀態
php artisan clickhouse:cluster:status --detailed

# 指定連接檢查叢集狀態
php artisan clickhouse:cluster:status --connection=cluster
```

### 遷移管理

```bash
# 創建遷移檔案
php artisan make:clickhouse-migration create_users_table
php artisan make:clickhouse-migration create_orders_table --table=orders
php artisan make:clickhouse-migration create_products_table --create --columns="name:string,price:decimal,is_active:bool"

# 執行遷移
php artisan clickhouse:migrate

# 預覽遷移 SQL
php artisan clickhouse:migrate --pretend

# 指定遷移路徑
php artisan clickhouse:migrate --path=database/migrations/clickhouse

# 回滾遷移
php artisan clickhouse:migrate:rollback

# 回滾指定數量
php artisan clickhouse:migrate:rollback --step=3
```

## 遷移檔案

### 重要說明

ClickHouse 遷移使用 Laravel 的預設 `migrations` 資料表來追蹤遷移狀態，而不是在 ClickHouse 中創建額外的資料表。這確保了遷移記錄與其他 Laravel 遷移保持一致。

### 創建遷移檔案

使用 `make:clickhouse-migration` 命令快速創建遷移檔案：

```bash
# 基本用法
php artisan make:clickhouse-migration create_users_table

# 指定表名
php artisan make:clickhouse-migration create_orders_table --table=orders

# 創建表（明確指定）
php artisan make:clickhouse-migration create_products_table --create

# 自定義欄位
php artisan make:clickhouse-migration create_analytics_table --columns="user_id:int,name:string,score:float,is_active:bool,tags:array"

# 指定路徑
php artisan make:clickhouse-migration create_test_table --path=database/migrations/custom
```

### 支援的欄位類型

命令支援以下欄位類型映射：

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

### 手動創建遷移檔案

```php
<?php

use Deflinhec\LaravelClickHouse\Database\Migration;

class CreateExampleTable extends Migration
{
    public function up()
    {
        $this->client->write("
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
        ");
    }

    public function down()
    {
        $this->client->write("DROP TABLE IF EXISTS example_table");
    }
}
```

## 配置選項

### 連接設定

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
],
```

### 效能設定

```php
'performance' => [
    'max_execution_time' => env('CLICKHOUSE_MAX_EXECUTION_TIME', 300),
    'max_memory_usage' => env('CLICKHOUSE_MAX_MEMORY_USAGE', 10000000000),
    'max_bytes_before_external_group_by' => env('CLICKHOUSE_MAX_BYTES_BEFORE_EXTERNAL_GROUP_BY', 2000000000),
    'max_bytes_before_external_sort' => env('CLICKHOUSE_MAX_BYTES_BEFORE_EXTERNAL_SORT', 2000000000),
],
```

## 測試

```bash
# 執行測試
php artisan clickhouse:test

# 測試特定功能
php artisan clickhouse:test --query="SELECT has(depth.identify, 'agent1') FROM your_table LIMIT 1"
```

## 資料表結構範例

### 基本資料表

標準的 ClickHouse 資料表結構範例：

```sql
CREATE TABLE example_table (
    id UInt32,
    name String,
    value Float64,
    created_at DateTime DEFAULT now()
) ENGINE = MergeTree()
ORDER BY (id, created_at)
```

### Nested 資料類型範例

使用 ClickHouse 的 Nested 資料類型來處理複雜的層級結構：

```sql
CREATE TABLE nested_example (
    id UInt32,
    depth Nested(identify String, ratio Decimal(8,2), rebate Decimal(8,2)),
    created_at DateTime DEFAULT now()
) ENGINE = MergeTree()
ORDER BY (id, created_at)
```

效益包括：
- 資料結構更精簡、節省儲存空間
- 查詢更具彈性（可使用 `has()`, `arrayJoin()` 等 ClickHouse 強項函數）
- 更容易進行深層結構的分析與維護

## 支援的 ClickHouse 函數

- `has()` - 檢查陣列中是否包含特定值
- `arrayJoin()` - 展開陣列
- `length()` - 獲取陣列長度
- `toDate()` - 日期轉換
- `row_number() OVER (PARTITION BY ... ORDER BY ...)` - 視窗函數

## 授權

MIT License
