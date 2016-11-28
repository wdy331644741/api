<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOneYuanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('one_yuan', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',64)->default('');//商品名
            $table->text('desc')->nullable();//说明
            $table->string('photo')->default('');//配图
            $table->integer('total_num',false,true)->default(0);//总次数
            $table->integer('buy_num',false,true)->default(0);//购买次数
            $table->timestamp('start_time')->nullable()->default(NULL);//开始时间
            $table->timestamp('end_time')->nullable()->default(NULL);//结束时间
            $table->tinyInteger('status',false,true)->default(0);//商品状态0未上线1上线
            $table->timestamp('release_time')->nullable()->default(NULL);//上线时间
            $table->timestamp('offline_time')->nullable()->default(NULL);//下线时间
            $table->integer('user_id',false,true)->default(0);//中奖的用户id
            $table->integer('buy_id',false,true)->default(0);//参与的奖品id
            $table->integer('code',false,true);//老时时彩码
            $table->integer('luck_code',false,true);//中奖码
            $table->bigInteger('total_times',false,true)->default(0);//50个用户的时间戳之和
            $table->integer('join_users',false,true)->default(0);//参与的人次
            $table->timestamp('luck_time')->default(null);//该用户中奖参与的时间
            $table->integer('priority',false,true)->default(0);//优先级
            $table->timestamps();
        });
        Schema::table('one_yuan', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('one_yuan');
    }
}
