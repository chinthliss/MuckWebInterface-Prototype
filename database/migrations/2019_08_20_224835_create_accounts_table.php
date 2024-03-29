<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //The existing server already has this table so need to check if it exists or not
        if (!Schema::hasTable('accounts')) {
            Schema::create('accounts', function (Blueprint $table) {
                $table->bigIncrements('aid')->unsigned();
                $table->char('uuid', 36)->index(); //Controlled by muck so not using uuid type
                $table->string('email')->index();
                $table->string('password');
                $table->enum('password_type', ['CLEARTEXT', 'LOCKED', 'SHA1SALT'])->default('LOCKED');
                //These are nullable due to existing nulls in production DB
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent();
                $table->timestamp('locked_at')->nullable();
                $table->rememberToken()->unique();
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
        throw new Error("Can not reverse this migration. Use 'artisan migrate:fresh --seed' for testing.");
    }
}
