<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillingItemcatalogueTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('billing_itemcatalogue')) {
            Schema::create('billing_itemcatalogue', function (Blueprint $table) {
                $table->char('code', 16)->primary();
                $table->string('name', 60);
                $table->string('description');
                $table->decimal('amount_usd', 8,2);
                $table->boolean('supporter')->nullable()
                    ->comment("Whether this item awards supporter points");
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
        Schema::dropIfExists('billing_itemcatalogue');
    }
}
