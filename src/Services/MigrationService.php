<?php

namespace Deflinhec\LaravelClickHouse\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Deflinhec\LaravelClickHouse\Exceptions\ClickHouseException;

class MigrationService
{
    /**
     * ClickHouse 客戶端實例
     *
     * @var \ClickHouseDB\Client
     */
    protected $client;

    /**
     * 遷移表名稱
     *
     * @var string
     */
    protected $migrationsTable;

    /**
     * 建構函數
     */
    public function __construct()
    {
        $this->client = app(\ClickHouseDB\Client::class);
        $this->migrationsTable = 'migrations'; // 使用 Laravel 的 migrations 資料表
    }

    /**
     * 獲取待執行的遷移
     *
     * @param string $path
     * @return array
     */
    public function getPendingMigrations($path)
    {
        $files = File::glob($path . '/*.php');
        $ranMigrations = $this->getRanMigrations();

        $pending = [];

        foreach ($files as $file) {
            $migration = $this->getMigrationName($file);

            if (!in_array($migration, $ranMigrations)) {
                $pending[] = [
                    'file' => $file,
                    'name' => $migration,
                ];
            }
        }

        return $pending;
    }

    /**
     * 獲取最後執行的遷移
     *
     * @param int $step
     * @return array
     */
    public function getLastMigrations($step = 1)
    {
        $lastBatch = DB::table($this->migrationsTable)
            ->max('batch');

        if (!$lastBatch) {
            return [];
        }

        $migrations = DB::table($this->migrationsTable)
            ->where('batch', $lastBatch)
            ->orderBy('id', 'desc')
            ->limit($step)
            ->get(['migration', 'batch']);

        return $migrations->map(function ($migration) {
            return [
                'name' => $migration->migration,
                'batch' => $migration->batch,
            ];
        })->toArray();
    }

    /**
     * 執行遷移
     *
     * @param array $migration
     * @return void
     */
    public function runMigration($migration)
    {
        $migrationInstance = $this->createMigrationInstance($migration);

        if ($migrationInstance) {
            try {
                $migrationInstance->runUp();
                $this->logMigration($migration['name']);
            } catch (\Exception $e) {
                Log::error("Migration {$migration['name']} failed: " . $e->getMessage());
                throw ClickHouseException::migrationError(
                    "Migration {$migration['name']} failed: " . $e->getMessage(),
                    [
                        'migration' => $migration['name'],
                        'exception' => $e->getMessage()
                    ]
                );
            }
        }
    }

    /**
     * 回滾遷移
     *
     * @param array $migration
     * @return void
     */
    public function rollbackMigration($migration)
    {
        $migrationInstance = $this->createMigrationInstance($migration);

        if ($migrationInstance) {
            try {
                $migrationInstance->runDown();
                $this->removeMigrationLog($migration['name']);
            } catch (\Exception $e) {
                Log::error("Migration rollback {$migration['name']} failed: " . $e->getMessage());
                throw ClickHouseException::migrationError(
                    "Migration rollback {$migration['name']} failed: " . $e->getMessage(),
                    [
                        'migration' => $migration['name'],
                        'exception' => $e->getMessage()
                    ]
                );
            }
        }
    }

    /**
     * 創建遷移實例
     *
     * @param array $migration
     * @return \Deflinhec\LaravelClickHouse\Database\Migration|null
     */
    public function createMigrationInstance($migration)
    {
        try {
            $migrationInstance = require $migration['file'];

            if ($migrationInstance instanceof \Deflinhec\LaravelClickHouse\Database\Migration) {
                return $migrationInstance;
            }
        } catch (\Exception $e) {
            Log::error('Failed to create migration instance: ' . $e->getMessage());
            throw ClickHouseException::migrationError(
                'Failed to create migration instance: ' . $e->getMessage(),
                [
                    'migration' => $migration['name'] ?? 'unknown',
                    'file' => $migration['file'] ?? 'unknown',
                    'exception' => $e->getMessage()
                ]
            );
        }

        return null;
    }



    /**
     * 記錄遷移執行
     *
     * @param string $migration
     * @return void
     */
    protected function logMigration($migration)
    {
        $batch = $this->getNextBatchNumber();

        DB::table($this->migrationsTable)->insert([
            'migration' => $migration,
            'batch' => $batch,
        ]);
    }

    /**
     * 移除遷移記錄
     *
     * @param string $migration
     * @return void
     */
    protected function removeMigrationLog($migration)
    {
        DB::table($this->migrationsTable)
            ->where('migration', $migration)
            ->delete();
    }

    /**
     * 獲取已執行的遷移列表
     *
     * @return array
     */
    protected function getRanMigrations()
    {
        return DB::table($this->migrationsTable)
            ->orderBy('id')
            ->pluck('migration')
            ->toArray();
    }

    /**
     * 獲取下一個批次號
     *
     * @return int
     */
    protected function getNextBatchNumber()
    {
        $maxBatch = DB::table($this->migrationsTable)->max('batch');
        return ($maxBatch ?? 0) + 1;
    }

    /**
     * 從檔案路徑獲取遷移名稱
     *
     * @param string $file
     * @return string
     */
    protected function getMigrationName($file)
    {
        return pathinfo($file, PATHINFO_FILENAME);
    }
}
