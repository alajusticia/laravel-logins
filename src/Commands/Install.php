<?php

namespace ALajusticia\Logins\Commands;

use ALajusticia\Logins\Helpers;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logins:install {--Q|quiet}';

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
        if (
            $this->option('quiet')
            || $this->confirm('This will run the database migrations and create the required files for Laravel Logins. Continue?', true)
        ) {

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

            if (
                Helpers::jetstreamIsInstalled()
                && Helpers::livewireIsInstalled()
                && Config::get('jetstream.stack') === 'livewire'
            ) {
                $this->comment('Creating files for Jetstream with Livewire stack...' . "\n");

                (new Filesystem)->ensureDirectoryExists(app_path('Livewire'));
                (new Filesystem)->ensureDirectoryExists(resource_path('views/livewire'));

                copy(__DIR__.'/../../stubs/jetstream-livewire/app/Livewire/Logins.php', app_path('Livewire/Logins.php'));
                copy(__DIR__.'/../../stubs/jetstream-livewire/resources/views/livewire/logins.blade.php', resource_path('views/livewire/logins.blade.php'));
            }

            $this->info('Installation was successful!');
        }
    }
}
