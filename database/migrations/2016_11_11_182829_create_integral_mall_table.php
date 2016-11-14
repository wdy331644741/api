<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIntegralMallTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('integral_mall', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('integral');//出售的积分值
            $table->text('desc')->nullable();//说明
            $table->string('photo');//配图
            $table->integer('total_quantity',false,true);//总量
            $table->integer('send_quantity',false,true);//送出数量
            $table->integer('award_type',false,true);//奖品类型
            $table->integer('award_id',false,true);//奖品id
            $table->integer('user_quantity',false,true)->nullable()->default(0);//用户兑换总量 0为不限
            $table->integer('priority',false,true);//优先级
            $table->string('groups',64);//分组
            $table->timestamp('start_time');//开始时间
            $table->timestamp('end_time');//结束时间
            $table->tinyInteger('status',false,true);//商品状态0未上线1上线
            $table->timestamp('release_time');//上线时间
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
        Schema::drop('integral_mall');
    }
}
