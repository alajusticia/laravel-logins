<?php

namespace ALajusticia\Logins\Tests;

use Illuminate\Support\Facades\Route;

class PackageRoutesTest extends TestCase
{
    public function test_package_routes_are_not_registered_by_default(): void
    {
        $this->assertFalse(Route::has('logins.index'));
        $this->assertFalse(Route::has('logins.destroy'));
        $this->assertFalse(Route::has('logins.destroyOthers'));
        $this->assertFalse(Route::has('logins.destroyAll'));
    }
}
