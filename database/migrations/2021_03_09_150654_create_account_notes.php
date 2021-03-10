<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountNotes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //The existing server already has this table so need to check if it exists or not
        if (!Schema::hasTable('account_notes')) {
            Schema::create('account_notes', function (Blueprint $table) {
                $table->bigIncrements('id')->unsigned();
                $table->bigInteger('aid')->unsigned();
                $table->bigInteger('when')->unsigned()
                    ->comment("Stored as UTC integer");
                $table->longText('message');
                $table->string('staff_member', 255);
                $table->string('game', 255);

                $table->index(['aid', 'game']);
            });
        }
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
