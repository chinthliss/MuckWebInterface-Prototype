<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('category', 80)->index();
            $table->string('title', 80);

            $table->bigInteger('from_aid')->nullable()->index();
            $table->bigInteger('from_muck_object_id')->nullable()->index()
                ->comment('Tickets without a muck_object reference are considered account tickets.');

            $table->timestamp('created_at')->useCurrent();
            $table->enum('status', ['new', 'open', 'pending', 'hold'])->default('new');
            $table->timestamp('status_at')->useCurrent()
                ->comment('Last time the status was changed.');
            $table->enum('closure_reason', ['completed', 'denied', 'duplicate'])->nullable();
            $table->timestamp('closed_at')->nullable()->index();
            $table->boolean('public')->default(false);

            $table->text('content');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tickets');
    }
}
