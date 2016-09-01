<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AwardBatch extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('award_batch', function (Blueprint $table) {
            $table->increments('id');
            $table->text('uids');//用户ID
            $table->integer('award_type',false,true);//奖品类型
            $table->integer('award_id',false,true);//奖品id
            $table->string('source_name',64);//来源名
            $table->tinyInteger('status',false,true);//0未开始1发送中2已完成
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
        Schema::drop('award_batch');
    }
}
