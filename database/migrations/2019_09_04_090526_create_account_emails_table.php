<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountEmailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //The existing server already has this table so need to check if it exists or not
        if (!Schema::hasTable('account_emails')) {
            Schema::create('account_emails', function (Blueprint $table) {
                $table->bigInteger('aid')->unsigned();
                $table->string('email')->index();
                $table->timestamp('verified_at')->nullable()->default(null);
                $table->timestamp('created_at')->nullable()->useCurrent();

                $table->primary(['aid', 'email']);
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
        Schema::dropIfExists('account_emails');
    }
}
