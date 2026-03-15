<?php

use ALajusticia\Logins\Http\Controllers\LoginsController;
use Illuminate\Support\Facades\Route;

// These endpoints use Laravel's default `web` middleware on purpose, to simplify the installation of the UI components and benefit from the session guard.

Route::prefix('api')
    ->middleware(['web', 'auth'])
    ->group(function () {
        Route::get('logins', [LoginsController::class, 'index'])->name('logins.index');
        Route::delete('logins/all', [LoginsController::class, 'destroyAll'])->name('logins.destroyAll');
        Route::delete('logins/others', [LoginsController::class, 'destroyOthers'])->name('logins.destroyOthers');
        Route::delete('logins/{loginId}', [LoginsController::class, 'destroy'])->name('logins.destroy');
    });
