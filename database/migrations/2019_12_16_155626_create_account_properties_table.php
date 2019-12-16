<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountPropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //The existing server already has this table so need to check if it exists or not
        if (!Schema::hasTable('account_properties')) {
            Schema::create('account_properties', function (Blueprint $table) {
                $table->bigInteger('aid')->unsigned();
                $table->char('propname', 100)->index();
                $table->longText('propdata');
                $table->enum('proptype', ['STRING', 'INTEGER', 'FLOAT', 'OBJECT']);

                $table->primary(['aid', 'propname']);
                $table->index(['aid']);
                $table->index(['propname', 'aid']);
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
        Schema::dropIfExists('account_properties');
    }
}
