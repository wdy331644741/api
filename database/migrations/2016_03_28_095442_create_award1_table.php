<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAward1Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('award_1', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',64);//名称
            $table->float('rate_increases',8,4);//加息值
            $table->tinyInteger('rate_increases_type',false,true);//加息时长类型 1、全周期2、加息天数3、局部加息时间段
            $table->tinyInteger('rate_increases_day',false,true);//加息时长天数
            $table->timestamp('rate_increases_start');//加息时长开始时间
            $table->timestamp('rate_increases_end');//加息时长结束时间
            $table->tinyInteger('effective_time_type',false,true);//有效时间类型
            $table->tinyInteger('effective_time_day',false,true);//有效时间顺延天数
            $table->timestamp('effective_time_start');//有效时间开始时间
            $table->timestamp('effective_time_end');//有效时间结束时间
            $table->integer('investment_threshold',false,true);//投资门槛
            $table->tinyInteger('project_duration_type',false,true);//项目期限
            $table->tinyInteger('project_type',false,true)->nullable();//项目类型
            $table->string('product_id',128)->nullable();//产品ID
            $table->tinyInteger('platform_type',false,true)->nullable();//平台端
            $table->string('limit_desc',32)->nullable();//限制说明
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
        Schema::drop('award_1');
    }
}
