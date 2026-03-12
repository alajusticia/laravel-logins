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

    public function test_detect_laravel_vue_starter_kit()
    {
        $inertiaPath = base_path('vendor/inertiajs/inertia-laravel/.gitkeep');
        $settingsRoutesPath = base_path('routes/settings.php');
        $profileControllerPath = app_path('Http/Controllers/Settings/ProfileController.php');
        $settingsLayoutPath = resource_path('js/layouts/settings/Layout.vue');
        $profilePagePath = resource_path('js/pages/settings/Profile.vue');

        $this->createFile($inertiaPath, '');
        $this->createFile($settingsRoutesPath, "<?php\n");
        $this->createFile($profileControllerPath, "<?php\n");
        $this->createFile($settingsLayoutPath, '<template></template>');
        $this->createFile($profilePagePath, '<template></template>');

        $this->assertTrue(Helpers::laravelVueStarterKitIsInstalled());

        unlink($profilePagePath);

        $this->assertFalse(Helpers::laravelVueStarterKitIsInstalled());
    }
}
