<?php

namespace ALajusticia\Logins\Commands;

use ALajusticia\Logins\Helpers\JetstreamHelpers;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class Jetstream extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logins:jetstream';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Laravel Logins for Jetstream.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! JetstreamHelpers::jetstreamIsInstalled()) {
            throw new \Exception('Jetstream is not installed!');
        }

        $this->comment('Installing for Jetstream...' . "\n");

        (new Filesystem)->ensureDirectoryExists(resource_path('views'));
        copy(__DIR__.'/../../stubs/livewire/resources/views/logins.blade.php', resource_path('views/logins.blade.php'));
    }
}
