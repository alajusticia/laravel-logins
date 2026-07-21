<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('logins') || ! Schema::hasColumn('logins', 'user_agent')) {
            return;
        }

        Schema::table('logins', function (Blueprint $table) {
            $table->text('user_agent')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('logins') || ! Schema::hasColumn('logins', 'user_agent')) {
            return;
        }

        Schema::table('logins', function (Blueprint $table) {
            $table->string('user_agent')->nullable()->change();
        });
    }
};
