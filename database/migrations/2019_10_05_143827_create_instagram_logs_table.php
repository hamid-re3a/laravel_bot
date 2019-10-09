<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInstagramLogsTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('instagram_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('instagram_id')->unsigned();
            $table->foreign('instagram_id')->references('id')->on('instagram_accounts');
            $table->timestamp('start_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('instagram_logs');
    }
}
