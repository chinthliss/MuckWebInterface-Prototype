<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillingSubscriptionsCombinedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('billing_subscriptions_combined', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->bigInteger('account_id')->index();

            $table->string('vendor', 60)->nullable()->index();

            $table->string('vendor_profile_id', 60)->nullable()->index();

            $table->string('vendor_subscription_id', 60)->nullable()->index();

            $table->string('vendor_subscription_plan_id', 60)->nullable();

            $table->decimal('amount_usd', 8, 2)->unsigned();

            $table->integer('recurring_interval')->unsigned()->nullable()
                ->comment('In days');

            $table->timestamp('created_at')->useCurrent()->index();

            $table->timestamp('closed_at')->nullable();

            $table->enum('status', [
                'approval_pending',
                'user_declined',
                'active',
                'suspended',
                'cancelled',
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
        Schema::dropIfExists('billing_subscriptions_combined');
    }
}
