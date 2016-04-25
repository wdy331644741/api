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
            $table->string('name',64);//名称
            $table->integer('red_max_money',false,true);//红包金额
            $table->integer('percentage',false,true);//红包金额
            $table->tinyInteger('effective_time_type',false,true);//有效时间类型
            $table->string('effective_time_info',32);//有效时间信息
            $table->integer('investment_threshold',false,true);//投资门槛
            $table->tinyInteger('project_duration_type',false,true);//项目期限
            $table->tinyInteger('project_type',false,true);//项目类型
            $table->tinyInteger('platform_type',false,true);//平台端
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
