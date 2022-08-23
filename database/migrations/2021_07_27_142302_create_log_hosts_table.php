<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLogHostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //The existing server already has this table so need to check if it exists or not
        if (!Schema::hasTable('log_hosts')) {
            Schema::create('log_hosts', function (Blueprint $table) {
                $table->char('host_ip', 16)->index();
                $table->string('host_name');
                $table->bigInteger('aid');
                $table->integer('tstamp');
                $table->integer('plyr_ref')->index(); // Can't be nullable as part of primary index
                $table->integer('plyr_tstamp')->nullable();
                $table->string('plyr_name', 50)->nullable();
                $table->tinyInteger('game_code')->unsigned();

                $table->primary(['host_ip', 'aid', 'plyr_ref', 'game_code']);
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
        throw new Error("Can not reverse this migration. Use 'migrate:fresh --seed' for testing.");
    }
}
