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

            $table->uuid('subscription_id')->nullable()->index()
                ->comment("Optional, used if transaction was related to a subscription");

            $table->string('vendor', 20);

            $table->string('vendor_profile_id', 60)->nullable()->index()
                ->comment("With Authorize.net this is actually the cardID");

            $table->string('vendor_transaction_id', 80)->nullable()->unique();

            $table->decimal('amount_usd', 8, 2)->unsigned()
                ->comment("The amount for directly buying account currency.");

            $table->decimal('amount_usd_items', 8, 2)->unsigned()
                ->comment("The amount for buying items.");

            $table->integer('accountcurrency_quoted')->unsigned();

            $table->integer('accountcurrency_rewarded')->unsigned()->nullable()
                ->comment("Separate since the final amount can change due to bonuses.");

            $table->integer('accountcurrency_rewarded_items')->unsigned()->nullable()
                ->comment("Any extra direct currency given from items");

            $table->json('items_json')->nullable()
                ->comment("JSON encoding of item purchased along with any additional information required.");

            $table->text('purchase_description')
                ->comment('Text description of what was purchased, primarily used for receipting purposes.');

            $table->integer('recurring_interval')->unsigned()->nullable()
                ->comment('In days');

            $table->timestamp('created_at')->useCurrent()->index();

            $table->timestamp('paid_at')->nullable();

            $table->timestamp('completed_at')->nullable()
                ->comment('Final date for this transaction. Does not imply success.');

            $table->enum('result', [
                'user_declined',
                'vendor_refused',
                'expired',
                'fulfilled'
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
