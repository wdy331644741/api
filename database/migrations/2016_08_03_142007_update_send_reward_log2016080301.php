<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateSendRewardLog2016080301 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('send_reward_log', function (Blueprint $table) {
            $table->string('uuid',64)->nullable()->change();//唯一ID
            $table->string('coupon_code')->nullable();//优惠码
            $table->tinyInteger('status')->nullable();//状态1失败，2成功
            $table->tinyInteger('message_status')->nullable()->default(null);//短信发送状态0、模板为空 1、发送失败 2、已发送
            $table->tinyInteger('mail_status')->nullable()->default(null);//站内信发送状态0、模板为空 1、发送失败 2、已发送
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('send_reward_log', function (Blueprint $table) {
            $table->dropColumn('coupon_code');//删除奖品类型
            $table->dropColumn('status');//删除奖品id
            $table->dropColumn('message_status');//短信发送状态0、模板为空 1、发送失败 2、已发送
            $table->dropColumn('mail_status');//站内信发送状态0、模板为空 1、发送失败 2、已发送
        });
    }
}
