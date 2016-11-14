<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIntegralMallExchangeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('integral_mall_exchange', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id',false,true);//用户id
            $table->integer('mall_id',false,true);//商品id
            $table->text('snapshot');//快照
            $table->tinyInteger('send_status',false,true);//发送状态0、未发送1、已发送
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
        Schema::drop('integral_mall_exchange');
    }
}
