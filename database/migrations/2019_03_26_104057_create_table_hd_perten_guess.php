<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableHdPertenGuess extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hd_perten_guess', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedTinyInteger('period')->comment('期数');
            $table->integer('user_id');
            $table->unsignedInteger('number')->default(0)->comment('注数');
            $table->unsignedTinyInteger('type')->comment('1涨/2跌');
            $table->unsignedTinyInteger('status')->comment('0未开奖/1一开奖');
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
        Schema::drop('hd_perten_guess');
    }
}
