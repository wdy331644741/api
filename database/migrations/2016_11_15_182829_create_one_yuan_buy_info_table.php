<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOneYuanBuyInfoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('one_yuan_buy_info', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id', false, true);//用户id
            $table->integer('mall_id', false, true);//商品id
            $table->integer('num', false, true);//次数
            $table->integer('start', false, true);//开始码
            $table->integer('end', false, true);//结束码
            $table->integer('buy_time', false, true);//购买时间用int
            $table->timestamps();
            //索引
            $table->index(['user_id', 'mall_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('one_yuan_buy_info');
    }
}
