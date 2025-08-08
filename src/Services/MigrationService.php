<?php

namespace Deflinhec\LaravelClickHouse\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Deflinhec\LaravelClickHouse\Exceptions\ClickHouseException;

class MigrationService
{
    /**
     * ClickHouse client instance
     *
     * @var \ClickHouseDB\Client
     */
    protected $client;

    /**
     * Migration table name
     *
     * @var string
     */
    protected $migrationsTable;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->client = app(\ClickHouseDB\Client::class);
        $this->migrationsTable = 'migrations'; // Use Laravel's migrations table
    }

    /**
     * Get pending migrations
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
     * Get last executed migrations
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
     * Run migration
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
     * Rollback migration
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
     * Create migration instance
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
     * Log migration execution
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
     * Remove migration log
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
     * Get list of executed migrations
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
     * Get next batch number
     *
     * @return int
     */
    protected function getNextBatchNumber()
    {
        $maxBatch = DB::table($this->migrationsTable)->max('batch');
        return ($maxBatch ?? 0) + 1;
    }

    /**
     * Get migration name from file path
     *
     * @param string $file
     * @return string
     */
    protected function getMigrationName($file)
    {
        return pathinfo($file, PATHINFO_FILENAME);
    }
}
