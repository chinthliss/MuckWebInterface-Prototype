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

            $table->bigInteger('account_id')->index();

            $table->bigInteger('paymentprofile_id')->nullable()->index()
                ->comment('Presently used if the payment was via Authorize.Net');

            $table->bigInteger('paymentprofile_id_txt')->nullable()->index()
                ->comment("Presently used if the payment was via PayPal");

            $table->string('external_id', 80)->nullable()->unique()
                ->comment("ID value for the external vendor handling the payment");

            $table->decimal('amount_usd',8,2)->unsigned();

            $table->integer('accountcurrency_quoted')->unsigned();

            $table->integer('accountcurrency_rewarded')->unsigned()->nullable()
                ->comment("Separate since the final amount can change due to bonuses.");

            $table->text('purchase_description')
                ->comment('Text description of what was purchased, primarily used for receipting purposes.');

            $table->integer('recurring_interval')->unsigned()->nullable()
                ->comment('In days');

            $table->timestamp('created_at')->useCurrent()->index();

            $table->timestamp('completed_at')->nullable()
                ->comment('Final date for this transaction. Does not imply success.');

            $table->enum('result', [
                'fulfilled',
                'user_declined',
                'vendor_refused',
                'expired'
            ])->nullable();

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
