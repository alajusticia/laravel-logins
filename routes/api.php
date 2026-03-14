<?php

use ALajusticia\Logins\Http\Controllers\LoginsController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')
    ->middleware(['api', 'auth'])
    ->group(function () {
        Route::get('logins', [LoginsController::class, 'index'])->name('logins.index');
        Route::delete('logins/all', [LoginsController::class, 'destroyAll'])->name('logins.destroyAll');
        Route::delete('logins/others', [LoginsController::class, 'destroyOthers'])->name('logins.destroyOthers');
        Route::delete('logins/{login}', [LoginsController::class, 'destroy'])->name('logins.destroy');
    });
