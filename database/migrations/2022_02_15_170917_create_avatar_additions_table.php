<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAvatarItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('avatar_items', function (Blueprint $table) {
            $table->string('name', 40)->primary();
            $table->enum('type', ['item', 'background']);
            $table->string('filename', 50);
            $table->string('requirement', 80)->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->bigInteger('owner_aid')->unsigned()->nullable()->index();
            $table->smallInteger('cost')->nullable();
            $table->integer('x')->nullable();
            $table->integer('y')->nullable();
            $table->integer('rotate')->nullable();
            $table->decimal('scale')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('avatar_items');
    }
}
