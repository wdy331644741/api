<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOneYuanUserInfoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('one_yuan_user_info', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id', false, true);//用户id
            $table->integer('num', false, true);//次数
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
        Schema::drop('one_yuan_user_info');
    }
}
