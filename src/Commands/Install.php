<?php

namespace ALajusticia\Logins\Commands;

use ALajusticia\Logins\Helpers\SanctumHelpers;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logins:install-prototype';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install command for Laravel Logins. Initial or after adding Sanctum.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->confirm('This will run the database migrations required for Laravel Logins. Continue?', true)) {

            $this->info('Installing...');

            $migrationPaths = [
                'vendor/alajusticia/laravel-logins/database/migrations',
            ];

            if (SanctumHelpers::sanctumIsInstalled()) {
                $migrationPaths[] = 'vendor/alajusticia/laravel-logins/database/migrations/sanctum';
            }

            $options = [
                '--path' => $migrationPaths,
            ];

            if ($databaseConnection = Config::get('logins.database_connection')) {
                $options['--database'] = $databaseConnection;
            }

            $this->call('migrate', $options);

            $this->info('Installation was successful!');
        }
    }
}
