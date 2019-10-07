<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateServicesTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('services', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('shop_id')->unsigned();
            $table->foreign('shop_id')->references('id')->on('shops');
            $table->string('name', 100);
            $table->integer('cost')->unsigned();
            $table->enum('type', ['rent', 'sell']);
            $table->integer('duration');        // Number of minutes
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('services');
    }
}
