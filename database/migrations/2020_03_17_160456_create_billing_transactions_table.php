<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillingTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('billing_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->bigInteger('paymentprofile_id');

            $table->bigInteger('account_id');

            $table->integer('amount_usd')->unsigned()
                ->comment('Not a decimal since partial dollar values aren\'t accepted.');

            $table->integer('amount_accountcurrency')->unsigned();

            $table->text('purchase_description')
                ->comment('Text description of what was purchased, primarily used for receipting purposes.');

            $table->integer('recurring_interval')->unsigned()->nullable()
                ->comment('In days');

            $table->timestamp('created_at')->useCurrent();

            $table->timestamp('completed_at')->nullable()
                ->comment('Final date for this transaction. Does not imply success.');

            $table->enum('result', [
                'paid',
                'refused',
                'expired'
            ])->nullable();

            $table->json('other')->nullable()
                ->comment('JSON column for additional details');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('billing_transactions');
    }
}
