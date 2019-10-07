<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrdersTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');
            $table->integer('sms_receiver_id')->unsigned();
            $table->foreign('sms_receiver_id')->references('id')->on('sms_receivers');
            $table->integer('shop_id')->unsigned();
            $table->foreign('shop_id')->references('id')->on('shops');
            $table->timestamp('datetime');
            $table->integer('cost')->unsigned();
            $table->integer('duration');        // Number of minutes
            $table->boolean('confirm');
            $table->text('description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('orders');
    }
}
