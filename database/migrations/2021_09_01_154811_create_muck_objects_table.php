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
            $table->timestamp('created_at')->default(0); // Stops DB from auto-populating 'on update'
            $table->enum('type', ['player', 'room', 'thing']);

            $table->bigInteger('aid')->nullable();
            $table->string('name', 255)->nullable();
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
