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
            $table->bigInteger('aid')->nullable()->index();
            $table->enum('interest', ['watch', 'work']);
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
