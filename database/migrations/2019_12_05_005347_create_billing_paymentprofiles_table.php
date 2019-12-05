<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillingPaymentProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //The existing server already has this table so need to check if it exists or not
        if (!Schema::hasTable('billing_paymentprofiles')) {
            Schema::create('billing_paymentprofiles', function (Blueprint $table) {
                $table->bigIncrements('id')->unsigned();
                $table->bigInteger('profileid');
                $table->bigInteger('paymentid');
                $table->char('firstname', 255);
                $table->char('lastname', 255);
                $table->char('cardtype',50);
                $table->char('maskedcardnum', 25);
                $table->char('expdate', 25);
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
        Schema::dropIfExists('billing_paymentprofiles');
    }
}
