<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMuckObjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('muck_objects', function (Blueprint $table) {
            $table->id('id');
            $table->tinyInteger('game_code');
            $table->integer('dbref');
            $table->timestamp('created_at')->useCurrent(); // We don't want current, but not setting a default causes the DB to assume things
            $table->enum('type', ['player', 'zombie', 'room', 'thing']);
            $table->string('name', 255);
            $table->timestamp('deleted_at')->nullable()
                ->comment('Only tracked for player objects, everything else is just deleted from this table.');

            $table->index(['game_code', 'dbref', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('muck_objects');
    }
}
