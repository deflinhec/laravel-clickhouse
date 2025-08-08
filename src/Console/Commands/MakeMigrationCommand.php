<?php

namespace Deflinhec\LaravelClickHouse\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeMigrationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:clickhouse-migration 
                            {name : The name of the migration}
                            {--table= : The table name}
                            {--create : Create a new table}
                            {--path= : The path where the migration file should be created}
                            {--columns= : Define table columns (format: name:type,name:type)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new ClickHouse migration file';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $name = $this->argument('name');
        $table = $this->option('table');
        $create = $this->option('create');
        $columns = $this->option('columns');
        $path = $this->option('path') ?: config('clickhouse.migrations.path', database_path('migrations/clickhouse'));

        // 確保目錄存在
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        // 生成檔案名稱
        $fileName = $this->getMigrationFileName($name);
        $filePath = $path . '/' . $fileName;

        // 生成遷移內容
        $content = $this->getMigrationContent($name, $table, $create, $columns);

        // 寫入檔案
        File::put($filePath, $content);

        $this->info("✅ ClickHouse migration created successfully: {$fileName}");
        $this->line("📁 Location: {$filePath}");

        // 顯示使用提示
        $this->line("");
        $this->line("🚀 Next steps:");
        $this->line("   1. Edit the migration file to customize your table structure");
        $this->line("   2. Run: php artisan clickhouse:migrate");
        $this->line("   3. To rollback: php artisan clickhouse:migrate:rollback");

        return 0;
    }

    /**
     * 獲取遷移檔案名稱
     *
     * @param string $name
     * @return string
     */
    protected function getMigrationFileName($name)
    {
        $timestamp = date('Y_m_d_His');

        return "{$timestamp}_{$name}.php";
    }

    /**
     * 獲取遷移檔案內容
     *
     * @param string $name
     * @param string|null $table
     * @param bool $create
     * @param string|null $columns
     * @return string
     */
    protected function getMigrationContent($name, $table = null, $create = false, $columns = null)
    {
        $className = Str::studly($name);

        if (!$table) {
            $table = $this->getTableNameFromMigrationName($name);
        }

        // 選擇適當的 stub 檔案
        $stub = $this->getStub($create);

        // 讀取 stub 內容
        $stubContent = File::get($stub);

        // 替換變數
        $content = $this->replaceStubVariables($stubContent, $className, $table, $columns);

        return $content;
    }

    /**
     * 獲取適當的 stub 檔案路徑
     *
     * @param bool $create
     * @return string
     */
    protected function getStub($create)
    {
        $stubPath = __DIR__ . '/../../../stubs/';

        if ($create) {
            return $stubPath . 'migration.create.stub';
        }

        return $stubPath . 'migration.stub';
    }

    /**
     * 替換 stub 中的變數
     *
     * @param string $stub
     * @param string $className
     * @param string $table
     * @param string|null $columns
     * @return string
     */
    protected function replaceStubVariables($stub, $className, $table, $columns = null)
    {
        $columnDefinitions = $this->getColumnDefinitions($columns);

        return str_replace(
            ['{{ class }}', '{{ table }}', '{{ columns }}'],
            [$className, $table, $columnDefinitions],
            $stub
        );
    }

    /**
     * 從遷移名稱獲取表名
     *
     * @param string $name
     * @return string
     */
    protected function getTableNameFromMigrationName($name)
    {
        // 移除常見的前綴和後綴
        $name = preg_replace('/^(create_|drop_|add_|remove_|update_)/', '', $name);
        $name = preg_replace('/_table$/', '', $name);

        return Str::snake($name);
    }

    /**
     * 獲取欄位定義
     *
     * @param string|null $columns
     * @return string
     */
    protected function getColumnDefinitions($columns = null)
    {
        $defaultColumns = [
            'id UInt32',
            'created_at DateTime DEFAULT now()',
            'updated_at DateTime DEFAULT now()'
        ];

        if ($columns) {
            $customColumns = $this->parseColumns($columns);
            $allColumns = array_merge($defaultColumns, $customColumns);
        } else {
            $allColumns = $defaultColumns;
        }

        return '                ' . implode(",\n                ", $allColumns);
    }

    /**
     * 解析欄位定義
     *
     * @param string $columns
     * @return array
     */
    protected function parseColumns($columns)
    {
        $parsedColumns = [];
        $columnPairs = explode(',', $columns);

        foreach ($columnPairs as $pair) {
            $parts = explode(':', trim($pair));
            if (count($parts) >= 2) {
                $name = trim($parts[0]);
                $type = trim($parts[1]);

                // 支援常見的 ClickHouse 資料類型
                $type = $this->normalizeColumnType($type);

                $parsedColumns[] = "{$name} {$type}";
            }
        }

        return $parsedColumns;
    }

    /**
     * 標準化欄位類型
     *
     * @param string $type
     * @return string
     */
    protected function normalizeColumnType($type)
    {
        $typeMap = [
            'string' => 'String',
            'int' => 'Int32',
            'integer' => 'Int32',
            'bigint' => 'Int64',
            'float' => 'Float32',
            'double' => 'Float64',
            'decimal' => 'Decimal(10,2)',
            'bool' => 'UInt8',
            'boolean' => 'UInt8',
            'date' => 'Date',
            'datetime' => 'DateTime',
            'timestamp' => 'DateTime',
            'array' => 'Array(String)',
            'json' => 'String',
        ];

        $lowerType = strtolower($type);

        return $typeMap[$lowerType] ?? $type;
    }
}
