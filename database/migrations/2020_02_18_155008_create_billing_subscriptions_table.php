<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillingSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //The existing server already has this table so need to check if it exists or not
        if (!Schema::hasTable('billing_subscriptions')) {
            Schema::create('billing_subscriptions', function (Blueprint $table) {
                $table->increments('id')->unsigned();
                $table->integer('profile')->unsigned();
                $table->integer('payid')->unsigned();
                $table->decimal('amount',6,2);

                $table->integer('interval');
                $table->integer('tcreated');
                $table->integer('tlastbilled');
                $table->integer('tlastfailed');
                $table->integer('failedtimes');
                $table->integer('billedtimes');

                $table->string('user1', 255);
                $table->string('user2', 255);
                $table->string('user3',255);
                $table->string('user4', 255);
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
        Schema::dropIfExists('billing_subscriptions');
    }
}
