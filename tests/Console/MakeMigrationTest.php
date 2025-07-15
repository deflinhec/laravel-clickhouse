<?php

namespace Deflinhec\LaravelClickHouse\Tests\Console;

use Deflinhec\LaravelClickHouse\Console\MakeClickHouseMigrationCommand;
use Deflinhec\LaravelClickHouse\Tests\TestCase;
use Illuminate\Support\Facades\File;

class MakeMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create migrations directory if it doesn't exist
        $migrationsPath = base_path('database/migrations/clickhouse');
        if (!File::exists($migrationsPath)) {
            File::makeDirectory($migrationsPath, 0755, true);
        }
    }

    public function testCanCreateMigration()
    {
        $date = date('Y_m_d_His');
        
        $this->artisan('make:clickhouse-migration', [
            'name' => 'test_migration'
        ])->assertExitCode(0);

        $this->assertFileExists(
            base_path('database/migrations/clickhouse/' . $date . '_test_migration.php')
        );
    }

    protected function tearDown(): void
    {
        // Clean up created files
        $migrationsPath = base_path('database/migrations/clickhouse');
        if (File::exists($migrationsPath)) {
            File::deleteDirectory($migrationsPath);
        }

        parent::tearDown();
    }
} 