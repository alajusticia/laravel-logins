<?php

namespace ALajusticia\Logins\Tests;

class InstallCommandTest extends TestCase
{
    public function test_it_installs_files_for_the_laravel_vue_starter_kit(): void
    {
        $this->createLaravelVueStarterKitFiles();
        $this->createFile(config_path('logins.php'), <<<'PHP'
<?php

return [
    'security_page_route' => null,
];
PHP);

        $this->artisan('logins:install', ['--quiet' => true])->assertExitCode(0);

        $this->assertFileExists(app_path('Http/Controllers/Settings/LoginController.php'));
        $this->assertFileExists(app_path('Http/Requests/Settings/DisconnectLoginRequest.php'));
        $this->assertFileExists(resource_path('js/components/ConfirmPasswordDialog.vue'));
        $this->assertFileExists(resource_path('js/pages/settings/Logins.vue'));

        $settingsRoutes = file_get_contents(base_path('routes/settings.php'));
        $settingsLayout = file_get_contents(resource_path('js/layouts/settings/Layout.vue'));
        $loginsConfig = file_get_contents(config_path('logins.php'));

        $this->assertNotFalse($settingsRoutes);
        $this->assertNotFalse($settingsLayout);
        $this->assertNotFalse($loginsConfig);

        $this->assertStringContainsString('use App\\Http\\Controllers\\Settings\\LoginController;', $settingsRoutes);
        $this->assertSame(
            1,
            substr_count($settingsRoutes, "Route::get('settings/logins', [LoginController::class, 'show'])->name('logins.show');")
        );
        $this->assertStringContainsString(
            "Route::delete('settings/logins', [LoginController::class, 'destroyAll'])->name('logins.destroyAll');",
            $settingsRoutes
        );
        $this->assertStringContainsString(
            "Route::delete('settings/logins/{login}', [LoginController::class, 'destroy'])",
            $settingsRoutes
        );

        $this->assertStringContainsString("import { show as showLogins } from '@/routes/logins';", $settingsLayout);
        $this->assertSame(1, substr_count($settingsLayout, "title: 'Active sessions'"));
        $this->assertStringContainsString("'security_page_route' => 'logins.show',", $loginsConfig);
    }

    protected function createLaravelVueStarterKitFiles(): void
    {
        $this->createFile(base_path('vendor/inertiajs/inertia-laravel/.gitkeep'), '');
        $this->createFile(app_path('Http/Controllers/Settings/ProfileController.php'), "<?php\n");
        $this->createFile(resource_path('js/pages/settings/Profile.vue'), "<template></template>\n");
        $this->createFile(base_path('routes/settings.php'), <<<'PHP'
<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('user-password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');

    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');
});
PHP);
        $this->createFile(resource_path('js/layouts/settings/Layout.vue'), <<<'VUE'
<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { show } from '@/routes/two-factor';
import { edit as editPassword } from '@/routes/user-password';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: editProfile(),
    },
    {
        title: 'Password',
        href: editPassword(),
    },
    {
        title: 'Two-factor auth',
        href: show(),
    },
    {
        title: 'Appearance',
        href: editAppearance(),
    },
];
</script>
VUE);
    }
}
