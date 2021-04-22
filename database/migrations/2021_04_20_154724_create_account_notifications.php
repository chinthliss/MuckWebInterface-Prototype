<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountNotifications extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_notifications', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->bigInteger('aid')->unsigned()->index();
            $table->string('game_code', 5)->nullable()
                ->comment('If null, assumed to be account-wide.');
            $table->bigInteger('character_dbref')->nullable()
                ->comment('If null, assumed to be either game-wide or account-wide depending on game_code.');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('read_at')->nullable();
            $table->text('message');

            $table->index(['game_code', 'aid']);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        throw new Error("Can not reverse this migration. Use 'migrate:fresh -seed' for testing.");
    }
}
