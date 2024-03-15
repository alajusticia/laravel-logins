<?php

use ALajusticia\Logins\Helpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Helpers::sanctumIsInstalled()) {
            throw new \Exception('Laravel Sanctum is not installed!');
        }

        Schema::table('logins', function (Blueprint $table) {
            $table->foreign('personal_access_token_id')
                ->references(app(Sanctum::personalAccessTokenModel())->getKeyName())
                ->on(app(Sanctum::personalAccessTokenModel())->getTable())
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logins', function (Blueprint $table) {
            $table->dropForeign('personal_access_token_id');
        });
    }
};
