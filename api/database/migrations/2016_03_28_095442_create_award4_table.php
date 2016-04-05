<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAward4Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('award_4', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',64);//关联活动
            $table->tinyInteger('experience_amount_type',false,true);//体验类型
            $table->string('experience_amount_info',10);//体验类型信息
            $table->tinyInteger('effective_time_type',false,true);//有效时间类型
            $table->string('effective_time_info',32);//有效时间信息
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
        Schema::drop('award_4');
    }
}
