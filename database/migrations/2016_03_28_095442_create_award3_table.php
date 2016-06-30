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
            $table->string('name',64);//体验金额名称
            $table->tinyInteger('experience_amount_type',false,true);//体验类型
            $table->integer('experience_amount_money',false,true);//体验金额
            $table->tinyInteger('experience_amount_multiple',false,true);//体验金额倍数
            $table->tinyInteger('effective_time_type',false,true);//有效时间类型
            $table->tinyInteger('effective_time_day',false,true);//有效时间顺延天数
            $table->timestamp('effective_time_start');//有效时间开始时间
            $table->timestamp('effective_time_end');//有效时间结束时间
//            $table->string('product_id',128)->nullable();//产品ID
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
        Schema::drop('award_3');
    }
}
