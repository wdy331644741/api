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
            $table->integer('user_id', false, true)->default(0);//用户id
            $table->integer('num', false, true)->default(0);//次数
            $table->timestamps();
            //索引
            $table->index('user_id');
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
