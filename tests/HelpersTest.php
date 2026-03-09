<?php

namespace ALajusticia\Logins\Tests;

use ALajusticia\Logins\Helpers;

class HelpersTest extends TestCase
{
    public function test_detect_livewire_starter_kit_single_file_variant()
    {
        $fluxPath = base_path('vendor/livewire/flux/.gitkeep');
        $settingsRoutesPath = base_path('routes/settings.php');
        $singleFileSettingsLayoutPath = resource_path('views/pages/settings/layout.blade.php');

        $this->createFile($fluxPath, '');
        $this->createFile($settingsRoutesPath, "<?php\n");
        $this->createFile($singleFileSettingsLayoutPath, '<div></div>');

        $this->assertTrue(Helpers::livewireStarterKitSingleFileVariantIsInstalled());
        $this->assertTrue(Helpers::livewireStarterKitIsInstalled());

        unlink($singleFileSettingsLayoutPath);

        $this->assertFalse(Helpers::livewireStarterKitSingleFileVariantIsInstalled());
    }

    public function test_detect_livewire_starter_kit_class_based_variant()
    {
        $fluxPath = base_path('vendor/livewire/flux/.gitkeep');
        $settingsRoutesPath = base_path('routes/settings.php');
        $classBasedProfileClassPath = app_path('Livewire/Settings/Profile.php');
        $classBasedProfileViewPath = resource_path('views/livewire/settings/profile.blade.php');
        $classBasedSettingsLayoutPath = resource_path('views/components/settings/layout.blade.php');

        $this->createFile($fluxPath, '');
        $this->createFile($settingsRoutesPath, "<?php\n");
        $this->createFile($classBasedProfileClassPath, "<?php\n");
        $this->createFile($classBasedProfileViewPath, '<div></div>');
        $this->createFile($classBasedSettingsLayoutPath, '<div></div>');

        $this->assertTrue(Helpers::livewireStarterKitClassBasedVariantIsInstalled());
        $this->assertTrue(Helpers::livewireStarterKitIsInstalled());

        unlink($classBasedProfileClassPath);

        $this->assertFalse(Helpers::livewireStarterKitClassBasedVariantIsInstalled());
    }

    protected function createFile(string $path, string $content): void
    {
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $content);
    }
}
