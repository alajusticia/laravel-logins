<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Logins are looked up by personal access token, and the foreign key deleting a login with its
     * token is not indexed automatically by every database (PostgreSQL, SQLite).
     */
    public function up(): void
    {
        if (! Schema::hasTable('logins') || $this->hasIndex()) {
            return;
        }

        Schema::table('logins', function (Blueprint $table) {
            $table->index('personal_access_token_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('logins') || ! $this->hasIndex('logins_personal_access_token_id_index')) {
            return;
        }

        Schema::table('logins', function (Blueprint $table) {
            $table->dropIndex(['personal_access_token_id']);
        });
    }

    /**
     * Determine if an index starts with the column (MySQL creates one for the foreign key, for example).
     */
    protected function hasIndex(?string $name = null): bool
    {
        if (! method_exists(Schema::getFacadeRoot(), 'getIndexes')) {
            return Schema::hasIndex('logins', $name ?? ['personal_access_token_id']);
        }

        return collect(Schema::getIndexes('logins'))->contains(
            fn (array $index): bool => $name !== null
                ? $index['name'] === $name
                : ($index['columns'][0] ?? null) === 'personal_access_token_id'
        );
    }
};
