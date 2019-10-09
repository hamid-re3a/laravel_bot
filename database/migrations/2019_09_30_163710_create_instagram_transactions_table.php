<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInstagramTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('instagram_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('telegram_user_id')->unsigned();
            $table->foreign('telegram_user_id')->references('telegram_id')->on('telegram_users');
            $table->integer('amount');
            $table->string('description',255);
            $table->boolean('confirm')->default(false);
            $table->string('photo', 255)->nullable();
            $table->integer('instagram_id')->unsigned();
            $table->foreign('instagram_id')->references('id')->on('instagram_accounts');
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
        Schema::dropIfExists('instagram_transactions');
    }
}
