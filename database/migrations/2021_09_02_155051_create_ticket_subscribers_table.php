<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketSubscribersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_subscribers', function (Blueprint $table) {
            $table->bigInteger('ticket_id')->index();
            $table->bigInteger('from_aid')->nullable()->index();
            $table->bigInteger('from_muck_object_id')->nullable();
            $table->enum('interest', ['owner', 'player', 'working']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ticket_subscribers');
    }
}
