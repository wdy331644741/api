<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAward3Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('award_3', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',64);//关联活动
            $table->tinyInteger('experience_amount_type',false,true);//体验类型
            $table->integer('experience_amount_money',false,true);//体验金额
            $table->tinyInteger('experience_amount_multiple',false,true);//体验金额倍数
            $table->tinyInteger('effective_time_type',false,true);//有效时间类型
            $table->tinyInteger('effective_time_day',false,true);//有效时间顺延天数
            $table->integer('effective_time_start',false,true);//有效时间开始时间
            $table->integer('effective_time_end',false,true);//有效时间结束时间
            $table->string('product_id',128);//产品ID
            $table->tinyInteger('platform_type',false,true);//平台端
            $table->string('limit_desc',32);//限制说明
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
        Schema::drop('award_3');
    }
}
