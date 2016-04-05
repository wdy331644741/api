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
            $table->integer('rate_increases',false,true);//加息值
            $table->tinyInteger('rate_increases_type',false,true);//加息时长类型 1、全周期2、局部加息
            $table->string('rate_increases_info',32);//加息时长信息
            $table->tinyInteger('effective_time_type',false,true);//有效时间类型
            $table->string('effective_time_info',32);//有效时间信息
            $table->integer('investment_threshold',false,true);//投资门槛
            $table->tinyInteger('project_duration_type',false,true);//项目期限
            $table->string('project_duration_info',32);//项目期限信息
            $table->tinyInteger('project_type',false,true);//项目类型
            $table->tinyInteger('repayment_type',false,true);//还款方式
            $table->tinyInteger('calculation_type',false,true);//计息方式
            $table->tinyInteger('product_type',false,true);//产品类型
            $table->string('product_type_info',32);//产品类型信息
            $table->tinyInteger('platform_type',false,true);//平台端
            $table->tinyInteger('activity_channel',false,true);//活动渠道
            $table->integer('created_at',false,true);//创建时间
            $table->integer('updated_at',false,true);//修改时间
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
