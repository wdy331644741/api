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
            $table->string('name',64);//商品名
            $table->text('desc')->nullable();//说明
            $table->string('photo');//配图
            $table->integer('total_num',false,true);//总次数
            $table->integer('buy_num',false,true);//购买次数
            $table->timestamp('exhibition');//展示日期
            $table->tinyInteger('status',false,true);//商品状态0未上线1上线
            $table->timestamp('release_time');//上线时间
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
