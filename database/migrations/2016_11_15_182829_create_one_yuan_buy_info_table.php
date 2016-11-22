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
            $table->integer('user_id', false, true)->default(0);//用户id
            $table->integer('mall_id', false, true)->default(0);//商品id
            $table->integer('num', false, true)->default(0);//次数
            $table->integer('start', false, true)->default(0);//开始码
            $table->integer('end', false, true)->default(0);//结束码
            $table->integer('buy_time', false, true)->default(0);//购买时间用int
            $table->timestamps();
            //索引
            $table->index(['user_id', 'mall_id']);
            $table->index('mall_id');
            $table->index(['start', 'end']);
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
