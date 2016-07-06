<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSendRewardLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('send_reward_log', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id',false,true);//用户id
            $table->integer('activity_id',false,true)->nullable();//活动id
            $table->integer('source_id',false,true);//来源id
            $table->integer('award_type',false,true);//奖品类型
            $table->string('uuid',64);//唯一id
            $table->text('remark')->nullable();//限制说明
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('send_reward_log');
    }
}
