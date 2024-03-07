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
        Schema::create('logins', function (Blueprint $table) {
            $table->id();
            $table->morphs('authenticatable');
            $table->string('user_agent')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('device_type')->nullable();
            $table->string('device')->nullable();
            $table->string('platform')->nullable();
            $table->string('browser')->nullable();
            $table->json('location')->nullable();
            $table->longText('payload');
            $table->string('remember_token')->nullable();
            $table->unsignedBigInteger('personal_access_token_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->integer('last_activity')->index();
            $table->expirable('expires_at');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logins');
    }
};
