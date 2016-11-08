<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSendRewardLogBatch extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('send_reward_log_batch', function (Blueprint $table) {
            $table->increments('id');
            $table->text('user_id');//用户id
            $table->integer('activity_id',false,true)->nullable();//活动id
            $table->integer('award_id',false,true);//活动id新增
            $table->integer('source_id',false,true);//来源id
            $table->integer('award_type',false,true);//奖品类型
            $table->integer('batch_id',false,true)->nullable()->default(0);//批量发送奖品批次ID
            $table->string('uuid',64)->nullable();//唯一ID
            $table->string('coupon_code')->nullable();//优惠码
            $table->tinyInteger('status')->nullable();//状态0失败，1成功
            $table->tinyInteger('message_status')->nullable()->default(null);//短信发送状态0、模板为空 1、发送失败 2、已发送
            $table->tinyInteger('mail_status')->nullable()->default(null);//站内信发送状态0、模板为空 1、发送失败 2、已发送
            $table->text('remark')->nullable();//限制说明
            $table->timestamps();
            //索引
            $table->index(['award_type', 'award_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('send_reward_log_batch');
    }
}
