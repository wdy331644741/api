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
            $table->timestamp('exhibition')->nullable()->default(NULL);//展示日期
            $table->tinyInteger('status',false,true)->default(0);//商品状态0未上线1上线
            $table->timestamp('release_time')->nullable()->default(NULL);//上线时间
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
