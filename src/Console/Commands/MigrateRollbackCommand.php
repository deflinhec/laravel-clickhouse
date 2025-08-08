<?php

namespace Deflinhec\LaravelClickHouse\Console\Commands;

use Illuminate\Console\Command;
use Deflinhec\LaravelClickHouse\Services\MigrationService;
use Deflinhec\LaravelClickHouse\Exceptions\ClickHouseException;

class MigrateRollbackCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clickhouse:migrate:rollback 
                            {--step=1 : The number of migrations to be reverted}
                            {--path= : The path to the migration files}
                            {--pretend : Dump the SQL queries that would be run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback ClickHouse migrations';

    /**
     * The migration service instance.
     *
     * @var MigrationService
     */
    protected $migrationService;

    /**
     * Create a new command instance.
     *
     * @param MigrationService $migrationService
     * @return void
     */
    public function __construct(MigrationService $migrationService)
    {
        parent::__construct();
        $this->migrationService = $migrationService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔄 Rolling back ClickHouse migrations...');

        $step = (int) $this->option('step');
        $path = $this->option('path') ?: config('clickhouse.migrations.path');
        $pretend = $this->option('pretend');

        try {
            $migrations = $this->migrationService->getLastMigrations($step);

            if (empty($migrations)) {
                $this->info('✅ No migrations to rollback.');
                return 0;
            }

            $this->info("📋 Rolling back " . count($migrations) . " migration(s)");

            foreach ($migrations as $migration) {
                $this->info("🔄 Rolling back migration: {$migration['name']}");

                if ($pretend) {
                    $this->pretendRollback($migration);
                } else {
                    $this->rollbackMigration($migration);
                }
            }

            $this->info('✅ ClickHouse migrations rollback completed successfully!');
            return 0;
        } catch (ClickHouseException $e) {
            $this->error("❌ ClickHouse Rollback Error ({$e->getErrorType()}): " . $e->getMessage());
            if ($this->output->isVerbose()) {
                $this->line("Error Code: {$e->getErrorCode()}");
                $this->line("Context: " . json_encode($e->getContext()));
            }
            return 1;
        } catch (\Exception $e) {
            $this->error("❌ Rollback failed: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Rollback a single migration.
     *
     * @param array $migration
     * @return void
     */
    protected function rollbackMigration($migration)
    {
        try {
            $this->migrationService->rollbackMigration($migration);
            $this->info("✅ Migration {$migration['name']} rolled back successfully");
        } catch (ClickHouseException $e) {
            $this->error("❌ ClickHouse Rollback Error ({$e->getErrorType()}): {$migration['name']} failed: " . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            $this->error("❌ Migration {$migration['name']} rollback failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Pretend to rollback a migration.
     *
     * @param array $migration
     * @return void
     */
    protected function pretendRollback($migration)
    {
        try {
            $migrationInstance = $this->migrationService->createMigrationInstance($migration);

            if ($migrationInstance) {
                $sql = $migrationInstance->down();
                $this->line("Rollback SQL for {$migration['name']}:");
                $this->line($sql);
                $this->line('');
            }
        } catch (\Exception $e) {
            $this->error("❌ Failed to get rollback SQL for {$migration['name']}: " . $e->getMessage());
        }
    }
}
