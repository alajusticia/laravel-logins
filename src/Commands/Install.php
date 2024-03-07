<?php

namespace ALajusticia\Logins\Commands;

use ALajusticia\Logins\Helpers\SanctumHelpers;
use Illuminate\Console\Command;

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

            $this->call('migrate', [
                '--path' => $migrationPaths,
            ]);

            $this->info('Installation was successful!');
        }
    }
}
