<?php

use ALajusticia\Logins\Helpers\SanctumHelpers;
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
        if (! SanctumHelpers::sanctumIsInstalled()) {
            throw new \Exception('Laravel Sanctum is not installed!');
        }

        Schema::table('logins', function (Blueprint $table) {
            $table->foreign('personal_access_token_id')
                ->references('id')
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
