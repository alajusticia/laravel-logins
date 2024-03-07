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

            $migrationsPath = __DIR__ . '/../../database/migrations';

            $this->info('Running migrations...');

            $this->call('migrate', [
                '--path' => $migrationsPath,
            ]);

            if (SanctumHelpers::sanctumIsInstalled()) {
                $this->call('migrate', [
                    '--path' => $migrationsPath . '/sanctum',
                ]);
            }

            $this->info('Installation was successful!');
        }
    }
}
