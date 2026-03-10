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
    protected $signature = 'logins:install {--force : Overwrite generated UI files}';

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

            $filesystem = new Filesystem();

            if (
                Helpers::jetstreamIsInstalled()
                && Helpers::livewireIsInstalled()
                && Config::get('jetstream.stack') === 'livewire'
            ) {
                $this->installJetstreamLivewireStackFiles($filesystem);
            }

            if (Helpers::livewireStarterKitClassBasedVariantIsInstalled()) {
                $this->installLivewireStarterKitClassBasedVariantFiles($filesystem);
            } elseif (Helpers::livewireStarterKitSingleFileVariantIsInstalled()) {
                $this->installLivewireStarterKitSingleFileVariantFiles($filesystem);
            }

            $this->info('Installation was successful!');
        }
    }

    /**
     * Install Jetstream (Livewire stack) component files.
     */
    protected function installJetstreamLivewireStackFiles(Filesystem $filesystem): void
    {
        $this->comment('Creating files for Jetstream with Livewire stack...' . "\n");

        $filesystem->ensureDirectoryExists(app_path('Livewire'));
        $filesystem->ensureDirectoryExists(resource_path('views/livewire'));

        $this->copyStubFile(
            __DIR__.'/../../stubs/jetstream-livewire/app/Livewire/Logins.php',
            app_path('Livewire/Logins.php')
        );

        $this->copyStubFile(
            __DIR__.'/../../stubs/jetstream-livewire/resources/views/livewire/logins.blade.php',
            resource_path('views/livewire/logins.blade.php')
        );
    }

    /**
     * Install Laravel Livewire Starter Kit (single-file variant) settings page files.
     */
    protected function installLivewireStarterKitSingleFileVariantFiles(Filesystem $filesystem): void
    {
        $this->comment('Creating files for Laravel Livewire Starter Kit (single-file variant)...' . "\n");

        $filesystem->ensureDirectoryExists(resource_path('views/pages/settings'));

        $this->copyStubFile(
            __DIR__.'/../../stubs/livewire-starter-kit/resources/views/pages/settings/logins.blade.php',
            resource_path('views/pages/settings/logins.blade.php'),
            (bool) $this->option('force')
        );

        $this->insertLineInFile(
            base_path('routes/settings.php'),
            "Route::livewire('settings/logins', 'pages::settings.logins')->name('logins.show');",
            "    Route::livewire('settings/logins', 'pages::settings.logins')->name('logins.show');\n",
            "    Route::livewire('settings/password', 'pages::settings.password')->name('user-password.edit');\n"
        );

        $this->insertLineInFile(
            resource_path('views/pages/settings/layout.blade.php'),
            "route('logins.show')",
            "            <flux:navlist.item :href=\"route('logins.show')\" wire:navigate>{{ __('Logins') }}</flux:navlist.item>\n",
            "            <flux:navlist.item :href=\"route('user-password.edit')\" wire:navigate>{{ __('Password') }}</flux:navlist.item>\n"
        );

        $this->setLivewireStarterKitSecurityRoute();
    }

    /**
     * Install Laravel Livewire Starter Kit (class-based variant) settings page files.
     */
    protected function installLivewireStarterKitClassBasedVariantFiles(Filesystem $filesystem): void
    {
        $this->comment('Creating files for Laravel Livewire Starter Kit (class-based variant)...' . "\n");

        $filesystem->ensureDirectoryExists(app_path('Livewire/Settings'));
        $filesystem->ensureDirectoryExists(resource_path('views/livewire/settings'));

        $this->copyStubFile(
            __DIR__.'/../../stubs/livewire-starter-kit-class-based/app/Livewire/Settings/Logins.php',
            app_path('Livewire/Settings/Logins.php'),
            (bool) $this->option('force')
        );

        $this->copyStubFile(
            __DIR__.'/../../stubs/livewire-starter-kit-class-based/resources/views/livewire/settings/logins.blade.php',
            resource_path('views/livewire/settings/logins.blade.php'),
            (bool) $this->option('force')
        );

        $this->insertLineInFile(
            base_path('routes/settings.php'),
            'use App\\Livewire\\Settings\\Logins;',
            "use App\\Livewire\\Settings\\Logins;\n",
            "use App\\Livewire\\Settings\\Appearance;\n"
        );

        $this->insertLineInFile(
            base_path('routes/settings.php'),
            "Route::livewire('settings/logins', Logins::class)->name('logins.show');",
            "    Route::livewire('settings/logins', Logins::class)->name('logins.show');\n",
            "    Route::livewire('settings/password', Password::class)->name('user-password.edit');\n"
        );

        $this->insertLineInFile(
            resource_path('views/components/settings/layout.blade.php'),
            "route('logins.show')",
            "            <flux:navlist.item :href=\"route('logins.show')\" wire:navigate>{{ __('Logins') }}</flux:navlist.item>\n",
            "            <flux:navlist.item :href=\"route('user-password.edit')\" wire:navigate>{{ __('Password') }}</flux:navlist.item>\n"
        );

        $this->setLivewireStarterKitSecurityRoute();
    }

    /**
     * Copy a stub file, optionally skipping existing destination.
     */
    protected function copyStubFile(string $source, string $destination, bool $overwrite = true): void
    {
        if (file_exists($destination) && ! $overwrite) {
            $this->line(sprintf('Skipping existing file [%s]. Use --force to overwrite it.', $destination));

            return;
        }

        copy($source, $destination);
    }

    /**
     * Insert a line in a file after a known existing line.
     */
    protected function insertLineInFile(string $path, string $contains, string $line, string $insertAfter): void
    {
        if (! file_exists($path)) {
            return;
        }

        $contents = file_get_contents($path);

        if ($contents === false || str_contains($contents, $contains)) {
            return;
        }

        if (str_contains($contents, $insertAfter)) {
            file_put_contents($path, str_replace($insertAfter, $insertAfter.$line, $contents));

            return;
        }

        $this->warn(sprintf('Could not auto update [%s]. Please add this manually.', $path));
    }

    /**
     * Set security page route for Livewire Starter Kit if config is publishable.
     */
    protected function setLivewireStarterKitSecurityRoute(): void
    {
        $path = config_path('logins.php');

        if (! file_exists($path)) {
            return;
        }

        $contents = file_get_contents($path);

        if ($contents === false || str_contains($contents, "'security_page_route' => 'logins.show',")) {
            return;
        }

        $updatedContents = str_replace(
            "'security_page_route' => null,",
            "'security_page_route' => 'logins.show',",
            $contents
        );

        if ($updatedContents === $contents) {
            return;
        }

        file_put_contents($path, $updatedContents);
    }
}
