<?php

use ALajusticia\Logins\Support\SanctumForeignKey;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        SanctumForeignKey::ensure();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        SanctumForeignKey::drop();
    }
};
