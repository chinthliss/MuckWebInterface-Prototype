<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatreonUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patreon_users', function (Blueprint $table) {
            $table->integer('patron_id')->unsigned()->primary();
            $table->string('email')->index()->nullable();
            $table->string('full_name', 100)->nullable();
            $table->string('vanity', 100)->nullable();
            $table->boolean('hide_pledges')->nullable();
            $table->string('url')->nullable();
            $table->string('thumb_url')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('patreon_users');
    }
}
