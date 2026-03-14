<?php

namespace ALajusticia\Logins\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use function Laravel\Prompts\select;

class Publish extends Command
{
    protected const STARTER_KITS = [
        'vue' => 'Laravel Vue Starter Kit',
        'livewire-single-file' => 'Laravel Livewire Starter Kit (single-file components)',
        'livewire-class-based' => 'Laravel Livewire Starter Kit (class-based components)',
        'jetstream-livewire' => 'Laravel Jetstream with Livewire',
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logins:publish
                            {--starter-kit= : The starter kit to publish components for [livewire-single-file, livewire-class-based, vue, jetstream-livewire]}
                            {--force : Overwrite existing component files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish the UI component for a supported starter kit.';

    /**
     * Execute the console command.
     */
    public function handle(Filesystem $filesystem): int
    {
        $starterKit = $this->resolveStarterKit();

        if ($starterKit === null) {
            return self::FAILURE;
        }

        $overwrite = (bool) $this->option('force');

        match ($starterKit) {
            'jetstream-livewire' => $this->publishJetstreamLivewireComponent($filesystem, $overwrite),
            'livewire-single-file' => $this->publishLivewireSingleFileComponent($filesystem, $overwrite),
            'livewire-class-based' => $this->publishLivewireClassBasedComponent($filesystem, $overwrite),
            'vue' => $this->publishVueComponent($filesystem, $overwrite),
        };

        $this->info('The UI component was published successfully.');

        return self::SUCCESS;
    }

    protected function resolveStarterKit(): ?string
    {
        $starterKit = $this->normalizeStarterKit($this->option('starter-kit'));

        if ($starterKit !== null) {
            return $starterKit;
        }

        if ($this->shouldPrompt()) {
            return $this->normalizeStarterKit(select(
                label: 'Which starter kit would you like to publish the UI component for?',
                options: self::STARTER_KITS
            ));
        }

        $this->error(sprintf(
            'The --starter-kit option is required when running non-interactively. Supported values: %s.',
            implode(', ', array_keys(self::STARTER_KITS))
        ));

        return null;
    }

    protected function normalizeStarterKit(mixed $starterKit): ?string
    {
        if (! is_string($starterKit) || trim($starterKit) === '') {
            return null;
        }

        $starterKit = strtolower(trim($starterKit));

        if (array_key_exists($starterKit, self::STARTER_KITS)) {
            return $starterKit;
        }

        $starterKitKey = array_search($starterKit, array_map('strtolower', self::STARTER_KITS), true);

        if ($starterKitKey !== false) {
            return $starterKitKey;
        }

        $this->error(sprintf(
            'Unsupported starter kit [%s]. Supported values: %s.',
            $starterKit,
            implode(', ', array_keys(self::STARTER_KITS))
        ));

        return null;
    }

    protected function publishJetstreamLivewireComponent(Filesystem $filesystem, bool $overwrite): void
    {
        $this->comment('Publishing the UI component for Laravel Jetstream with Livewire...' . "\n");

        $filesystem->ensureDirectoryExists(app_path('Livewire'));
        $filesystem->ensureDirectoryExists(resource_path('views/livewire'));

        $this->copyStubFile(
            $filesystem,
            __DIR__.'/../../stubs/jetstream-livewire/app/Livewire/Logins.php',
            app_path('Livewire/Logins.php'),
            $overwrite
        );

        $this->copyStubFile(
            $filesystem,
            __DIR__.'/../../stubs/jetstream-livewire/resources/views/livewire/logins.blade.php',
            resource_path('views/livewire/logins.blade.php'),
            $overwrite
        );
    }

    protected function publishLivewireSingleFileComponent(Filesystem $filesystem, bool $overwrite): void
    {
        $this->comment('Publishing the UI component for the Laravel Livewire Starter Kit (single-file variant)...' . "\n");

        $filesystem->ensureDirectoryExists(resource_path('views/livewire'));

        $this->copyStubFile(
            $filesystem,
            __DIR__.'/../../stubs/livewire-starter-kit/resources/views/livewire/logins.blade.php',
            resource_path('views/livewire/logins.blade.php'),
            $overwrite
        );
    }

    protected function publishLivewireClassBasedComponent(Filesystem $filesystem, bool $overwrite): void
    {
        $this->comment('Publishing the UI component for the Laravel Livewire Starter Kit (class-based variant)...' . "\n");

        $filesystem->ensureDirectoryExists(app_path('Livewire'));
        $filesystem->ensureDirectoryExists(resource_path('views/livewire'));

        $this->copyStubFile(
            $filesystem,
            __DIR__.'/../../stubs/livewire-starter-kit-class-based/app/Livewire/Logins.php',
            app_path('Livewire/Logins.php'),
            $overwrite
        );

        $this->copyStubFile(
            $filesystem,
            __DIR__.'/../../stubs/livewire-starter-kit-class-based/resources/views/livewire/logins.blade.php',
            resource_path('views/livewire/logins.blade.php'),
            $overwrite
        );
    }

    protected function publishVueComponent(Filesystem $filesystem, bool $overwrite): void
    {
        $this->comment('Publishing the UI component for the Laravel Vue Starter Kit...' . "\n");

        $filesystem->ensureDirectoryExists(app_path('Http/Controllers'));
        $filesystem->ensureDirectoryExists(resource_path('js/components'));

        $this->copyStubFile(
            $filesystem,
            __DIR__.'/../../stubs/vue-starter-kit/app/Http/Controllers/LoginsController.php',
            app_path('Http/Controllers/LoginsController.php'),
            $overwrite
        );

        $this->copyStubFile(
            $filesystem,
            __DIR__.'/../../stubs/vue-starter-kit/resources/js/components/Logins.vue',
            resource_path('js/components/Logins.vue'),
            $overwrite
        );
    }

    protected function copyStubFile(Filesystem $filesystem, string $source, string $destination, bool $overwrite): void
    {
        if (file_exists($destination) && ! $overwrite) {
            $this->line(sprintf('Skipping existing file [%s]. Use --force to overwrite it.', $destination));

            return;
        }

        $filesystem->copy($source, $destination);
    }

    protected function shouldPrompt(): bool
    {
        return $this->input->isInteractive() && ! $this->output->isQuiet();
    }
}
