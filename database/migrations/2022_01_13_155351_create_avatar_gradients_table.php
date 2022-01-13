<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAvatarGradientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('avatar_gradients', function (Blueprint $table) {
            $table->string('name', 40)->primary();
            $table->string('description', 200);
            $table->bigInteger('owner_aid')->unsigned()->nullable()->index();
            $table->boolean('free')->nullable();
            $table->json('steps_json');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('avatar_gradients');
    }
}
