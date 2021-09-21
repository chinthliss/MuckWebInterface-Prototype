<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_log', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('ticket_id')->index();
            $table->timestamp('created_at')->useCurrent();
            $table->enum('type', ['system', 'note', 'upvote', 'downvote'])->default('system');
            $table->boolean('staff_only');
            $table->bigInteger('from_aid')->nullable();
            $table->bigInteger('from_muck_object_id')->nullable();
            $table->text('content')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ticket_log');
    }
}
