<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInstagramAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('instagram_accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('telegram_user_id')->unsigned();
            $table->foreign('telegram_user_id')->references('telegram_id')->on('telegram_users');
            $table->string('username',20)->unique();
            $table->string('password')->nullable();
            $table->text('cookie')->nullable();
            $table->timestamp('paid_until')->nullable();
            $table->boolean('comment')->nullable()->default(false);
            $table->boolean('follow')->nullable()->default(false);
            $table->boolean('is_credentials_valid')->nullable()->default(false);
            $table->boolean('is_two_step_verification_valid')->nullable()->default(false);
            $table->boolean('user_pass_changed')->nullable()->default(true);
            $table->boolean('two_step_verification_changed')->nullable()->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('instagram_accounts');
    }
}
