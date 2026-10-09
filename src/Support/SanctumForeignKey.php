<?php

namespace ALajusticia\Logins\Support;

use ALajusticia\Logins\Helpers;
use ALajusticia\Logins\Models\Login;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;

/**
 * The foreign key deleting the login of a Sanctum personal access token together with the token,
 * whatever deletes it (revocation, password change, pruning of expired tokens…).
 *
 * Added by the Sanctum migration and again after each migration run, as the application may create
 * the personal access tokens table after the package's migration (migrations run by name).
 */
class SanctumForeignKey
{
    /**
     * Add the foreign key if it is missing and both tables exist in the same database.
     */
    public static function ensure(): void
    {
        if (! Helpers::sanctumIsInstalled()) {
            return;
        }

        $schema = static::schema();
        $tokenModel = app(Sanctum::personalAccessTokenModel());
        $tokensTable = $tokenModel->getTable();

        if (! $schema->hasTable('logins') || ! $schema->hasTable($tokensTable) || static::exists()) {
            return;
        }

        // Delete the logins of tokens deleted before the foreign key existed, which would prevent adding it
        $schema->getConnection()->table('logins')
            ->whereNotNull('personal_access_token_id')
            ->whereNotIn('personal_access_token_id', $schema->getConnection()->table($tokensTable)->select($tokenModel->getKeyName()))
            ->delete();

        $schema->table('logins', function (Blueprint $table) use ($tokenModel, $tokensTable) {
            $table->foreign('personal_access_token_id')
                ->references($tokenModel->getKeyName())
                ->on($tokensTable)
                ->cascadeOnDelete();
        });
    }

    /**
     * Drop the foreign key if it exists.
     */
    public static function drop(): void
    {
        if (! static::schema()->hasTable('logins') || ! static::exists()) {
            return;
        }

        static::schema()->table('logins', function (Blueprint $table) {
            $table->dropForeign(['personal_access_token_id']);
        });
    }

    /**
     * Determine if the foreign key exists (it may also have been added by the application).
     */
    public static function exists(): bool
    {
        $schema = static::schema();

        if (! method_exists($schema, 'getForeignKeys')) {
            return false;
        }

        return collect($schema->getForeignKeys('logins'))
            ->contains(fn (array $foreignKey): bool => $foreignKey['columns'] === ['personal_access_token_id']);
    }

    /**
     * The schema builder of the logins' database connection.
     */
    protected static function schema(): Builder
    {
        return Schema::connection((new Login)->getConnectionName());
    }
}
