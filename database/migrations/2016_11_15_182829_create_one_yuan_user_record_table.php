<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOneYuanUserRecordTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('one_yuan_user_record', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id', false, true)->default(0);//用户id
            $table->string('uuid', 64)->default('');//唯一id
            $table->integer('num', false, true)->default(0);//产生次数
            $table->string('source',64)->default('');//来源
            $table->text('snapshot')->default('');//快照
            $table->integer('type', false, true);//0添加积分1扣除积分
            $table->integer('status', false, true);//0添加积分1扣除积分
            $table->text('remark');//错误信息
            $table->timestamp('operation_time', false, true)->nullable()->default(NULL);//记录产生时间
            //索引
            $table->index('user_id');
            $table->index('source');
            $table->index('uuid');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('one_yuan_user_record');
    }
}
