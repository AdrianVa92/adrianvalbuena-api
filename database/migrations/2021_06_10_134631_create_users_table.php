<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('code', 36)->unique()->nullable();
            $table->string('username', 254)->unique()->nullable(false);
            $table->string('password', 60)->nullable(false);
            $table->string('email', 254)->unique()->nullable(false);
            $table->string('name', 254);
            $table->json('meta')->nullable();
            $table->json('sso')->nullable();
            $table->string('role', 10)->nullable(false)->default('member')->comment('admin, client, manager, moderator, member');
            $table->string('status', 10)->nullable(false)->default('active')->comment('active, suspended, inactive');

            $table->tinyInteger('reset_attempt_count')->nullable();
            $table->timestamp('reset_attempt_expired_at')->nullable();
            $table->string('reset_token', 36)->nullable();
            $table->timestamp('reset_token_expired_at')->nullable();
            $table->tinyInteger('login_attempt_count')->nullable();
            $table->timestamp('login_attempt_expired_at')->nullable();

            $table->index('code');
            $table->index('username');
            $table->index('email');
            $table->index('name');
            $table->index('role');
            $table->index('status');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
