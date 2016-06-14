<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAward2Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('award_2', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',64);//名称
            $table->integer('red_type',false,true);//红包金额1直抵红包2百分比红包
            $table->integer('red_money',false,true);//红包金额
            $table->integer('percentage',false,true);//红包百分比
            $table->tinyInteger('effective_time_type',false,true);//有效时间类型
            $table->tinyInteger('effective_time_day',false,true);//有效时间顺延天数
            $table->integer('effective_time_start',false,true);//有效时间开始时间
            $table->integer('effective_time_end',false,true);//有效时间结束时间
            $table->integer('investment_threshold',false,true);//投资门槛
            $table->tinyInteger('project_duration_type',false,true);//项目期限
            $table->tinyInteger('project_type',false,true)->nullable();//项目类型
            $table->string('product_id',128)->nullable();//产品ID
            $table->tinyInteger('platform_type',false,true)->nullable();//平台端
            $table->string('limit_desc',32)->nullable();//限制说明
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
        Schema::drop('award_2');
    }
}
