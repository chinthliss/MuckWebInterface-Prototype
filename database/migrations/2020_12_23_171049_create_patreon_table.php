<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatreonTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //The existing server already has this table so need to check if it exists or not
        if (!Schema::hasTable('patreon')) {

            Schema::create('patreon', function (Blueprint $table) {
                $table->integer('campaign_id');
                $table->integer('patron_id');
                $table->string('email')->index()->nullable();
                $table->string('full_name', 100)->nullable();
                $table->string('vanity', 100)->nullable();
                $table->char('hide_pledges', 1)->nullable();
                $table->integer('currently_entitled_amount_cents')->nullable();
                $table->char('is_follower', 1)->nullable();
                $table->string('last_charge_status', 8)->nullable()
                    ->comment('One of Paid, Declined, Deleted, Pending, Refunded, Fraud, Other. Can be null.');
                $table->dateTime('last_charge_date')->nullable();
                $table->integer('lifetime_support_cents')->nullable();
                $table->string('patron_status', 15)->nullable()
                    ->comment('One of active_patron, declined_patron, former_patron. Can be null.');
                $table->dateTime('pledge_relationship_start')->nullable();
                $table->string('url')->nullable();
                $table->string('thumb_url')->nullable();
                $table->dateTime('updated_at')->nullable();

                $table->primary(['campaign_id', 'patron_id']);
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
        Schema::dropIfExists('patreon');
    }
}
