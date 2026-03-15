<?php

namespace ALajusticia\Logins\Commands;

use ALajusticia\Logins\Helpers;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use function Laravel\Prompts\confirm;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logins:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install command for Laravel Logins';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->shouldPrompt() && ! confirm(
            label: 'This will run the database migrations required by Laravel Logins. Continue?',
            default: false
        )) {
            return self::SUCCESS;
        }

        $this->line('Installing...' . "\n");

        $migrationPaths = [
            'vendor/alajusticia/laravel-logins/database/migrations',
        ];

        if (Helpers::sanctumIsInstalled()) {
            $migrationPaths[] = 'vendor/alajusticia/laravel-logins/database/migrations/sanctum';
        }

        $options = [
            '--path' => $migrationPaths,
        ];

        if ($databaseConnection = Config::get('logins.database_connection')) {
            $options['--database'] = $databaseConnection;
        }

        $this->comment('Running migrations...' . "\n");

        $this->call('migrate', $options);

        $this->info('Installation was successful!');

        return self::SUCCESS;
    }

    protected function shouldPrompt(): bool
    {
        return $this->input->isInteractive() && ! $this->output->isQuiet();
    }
}
