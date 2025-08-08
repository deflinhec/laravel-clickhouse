<?php

namespace Deflinhec\LaravelClickHouse\Console\Commands;

use Illuminate\Console\Command;

class CliCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clickhouse 
                            {--connection= : The connection to use}
                            {--query= : Execute a single query and exit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Open ClickHouse client CLI';

    /**
     * ClickHouse client instance.
     *
     * @var \ClickHouseDB\Client
     */
    protected $client;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->initializeClient();

        if ($query = $this->option('query')) {
            return $this->executeSingleQuery($query);
        }

        return $this->startInteractiveCli();
    }

    /**
     * Initialize ClickHouse client.
     *
     * @return void
     */
    protected function initializeClient()
    {
        $connection = $this->option('connection') ?: config('clickhouse.default');
        $config = config("clickhouse.connections.{$connection}");

        if (!$config) {
            $this->error("❌ Connection '{$connection}' not found in clickhouse config");
            exit(1);
        }

        $this->client = new \ClickHouseDB\Client([
            'host' => $config['host'],
            'port' => $config['port'],
            'username' => $config['username'],
            'password' => $config['password'],
        ]);

        try {
            $this->client->database($config['database']);

            if ($config['options']['ssl'] ?? false) {
                $this->client->https(true);
            }

            $this->client->setTimeout($config['options']['timeout'] ?? 30);

            if ($config['options']['readonly'] ?? false) {
                $this->client->setReadOnlyUser(true);
            }

            // Test connection
            $this->client->select('SELECT 1 as test');
            $this->info("✅ Connected to ClickHouse at {$config['host']}:{$config['port']}");
            $this->info("📊 Database: {$config['database']}");
            $this->line('');
        } catch (\Exception $e) {
            $this->error("❌ Failed to connect to ClickHouse: " . $e->getMessage());
            exit(1);
        }
    }

    /**
     * Execute a single query and exit.
     *
     * @param string $query
     * @return int
     */
    protected function executeSingleQuery($query)
    {
        try {
            $result = $this->client->select($query);
            $this->displayResult($result);
            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Query failed: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Start interactive CLI.
     *
     * @return int
     */
    protected function startInteractiveCli()
    {
        $this->info('🚀 ClickHouse CLI - Interactive Mode');
        $this->info('Type "exit" or "quit" to exit, "help" for available commands');
        $this->line('');

        while (true) {
            try {
                $input = $this->ask('clickhouse> ');

                if (empty(trim($input))) {
                    continue;
                }

                $command = strtolower(trim($input));

                if (in_array($command, ['exit', 'quit', 'q'])) {
                    $this->info('👋 Goodbye!');
                    break;
                }

                if ($command === 'help') {
                    $this->showHelp();
                    continue;
                }

                if ($command === 'clear') {
                    $this->output->write("\033[2J\033[H");
                    continue;
                }

                // Execute SQL query
                $result = $this->client->select($input);
                $this->displayResult($result);
            } catch (\Exception $e) {
                $this->error("❌ Error: " . $e->getMessage());
            }
        }

        return 0;
    }

    /**
     * Display query result.
     *
     * @param mixed $result
     * @return void
     */
    protected function displayResult($result)
    {
        if (!$result || !$result->rows()) {
            $this->info('✅ Query executed successfully (no results)');
            return;
        }

        $rows = $result->rows();
        $headers = array_keys($rows[0]);

        $this->table($headers, $rows);
        $this->info("📊 Total rows: " . count($rows));
    }

    /**
     * Show help information.
     *
     * @return void
     */
    protected function showHelp()
    {
        $this->line('');
        $this->info('📖 Available Commands:');
        $this->line('  exit, quit, q    - Exit the CLI');
        $this->line('  help             - Show this help');
        $this->line('  clear            - Clear the screen');
        $this->line('');
        $this->info('📝 SQL Examples:');
        $this->line('  SELECT * FROM table LIMIT 10');
        $this->line('  SHOW TABLES');
        $this->line('  DESCRIBE table');
        $this->line('  SELECT count() FROM table');
        $this->line('');
    }
}
