<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketLinksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_links', function (Blueprint $table) {
            $table->bigInteger('from_ticket_id')->index();
            $table->bigInteger('to_ticket_id')->index();
            $table->enum('link_type', ['duplicate', 'related']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ticket_links');
    }
}
