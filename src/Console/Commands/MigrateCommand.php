<?php

namespace Deflinhec\LaravelClickHouse\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Deflinhec\LaravelClickHouse\Services\MigrationService;
use Deflinhec\LaravelClickHouse\Exceptions\ClickHouseException;

class MigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clickhouse:migrate 
                            {--path= : The path to the migration files}
                            {--force : Force the operation to run when in production}
                            {--pretend : Dump the SQL queries that would be run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run ClickHouse migrations';

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
        $this->info('🚀 Running ClickHouse migrations...');

        $path = $this->option('path') ?: config('clickhouse.migrations.path');
        $pretend = $this->option('pretend');

        if (!File::exists($path)) {
            $this->error("❌ Migration path does not exist: {$path}");
            return 1;
        }

        try {
            $migrations = $this->migrationService->getPendingMigrations($path);

            if (empty($migrations)) {
                $this->info('✅ No pending migrations to run.');
                return 0;
            }

            $this->info("📋 Found " . count($migrations) . " pending migration(s)");

            foreach ($migrations as $migration) {
                $this->info("🔄 Running migration: {$migration['name']}");

                if ($pretend) {
                    $this->pretendMigration($migration);
                } else {
                    $this->runMigration($migration);
                }
            }

            $this->info('✅ All ClickHouse migrations completed successfully!');
            return 0;
        } catch (ClickHouseException $e) {
            $this->error("❌ ClickHouse Migration Error ({$e->getErrorType()}): " . $e->getMessage());
            if ($this->output->isVerbose()) {
                $this->line("Error Code: {$e->getErrorCode()}");
                $this->line("Context: " . json_encode($e->getContext()));
            }
            return 1;
        } catch (\Exception $e) {
            $this->error("❌ Migration failed: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Run a single migration.
     *
     * @param array $migration
     * @return void
     */
    protected function runMigration($migration)
    {
        try {
            $this->migrationService->runMigration($migration);
            $this->info("✅ Migration {$migration['name']} completed successfully");
        } catch (ClickHouseException $e) {
            $this->error("❌ ClickHouse Migration Error ({$e->getErrorType()}): {$migration['name']} failed: " . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            $this->error("❌ Migration {$migration['name']} failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Pretend to run a migration.
     *
     * @param array $migration
     * @return void
     */
    protected function pretendMigration($migration)
    {
        try {
            $migrationInstance = $this->migrationService->createMigrationInstance($migration);

            if ($migrationInstance) {
                $sql = $migrationInstance->up();
                $this->line("SQL for {$migration['name']}:");
                $this->line($sql);
                $this->line('');
            }
        } catch (\Exception $e) {
            $this->error("❌ Failed to get SQL for {$migration['name']}: " . $e->getMessage());
        }
    }
}
