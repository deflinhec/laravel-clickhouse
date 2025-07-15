<?php

declare(strict_types=1);

namespace Deflinhec\LaravelClickHouse\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

class MigrateMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:clickhouse-migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new ClickHouse migration file';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'ClickHouse Migration';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/stubs/migration.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\ClickHouse';
    }

    /**
     * Get the destination class path.
     *
     * @param string $name
     * @return string
     */
    protected function getPath($name)
    {
        $base = class_basename($name);

        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        $name = Str::replaceFirst($base, date('Y_m_d_His') . '_' . Str::snake($base), $name);

        return Str::lower($this->laravel->databasePath('migrations')
            . '/' . str_replace('\\', '/', $name) . '.php');
    }

    protected function replaceClass($stub, $name)
    {
        $class = str_replace($this->getNamespace($name) . '\\', '', $name);

        $class = Str::studly($class);

        foreach (['DummyClass', '{{ class }}', '{{class}}'] as $search) {
            $stub = str_replace($search, $class, $stub);
        }

        return $stub;
    }
}
