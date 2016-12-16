<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMoneyShareInfo20161207 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('money_share_info', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id',false,true)->nullable()->default(0)->comment("用户id");
            $table->integer('main_id',false,true)->nullable()->default(0)->comment("主表id");
            $table->string('uuid',64)->nullable()->default(0)->comment("唯一id");
            $table->integer('money',false,true)->nullable()->default(0)->comment("得到金额");
            $table->integer('source_id',false,true)->nullable()->default(0)->comment("来源id");
            $table->integer('award_type',false,true)->nullable()->default(0)->comment("奖品类型");
            $table->integer('award_id',false,true)->nullable()->default(0)->comment("奖品id");
            $table->text('remark')->nullable()->default('')->comment("说明");
            $table->tinyInteger('mail_status',false,true)->nullable()->default(0)->comment("站内信发送状态0未发送或没配置模板1已发送");
            $table->tinyInteger('message_status',false,true)->nullable()->default(0)->comment("短信发送状态0未发送或没配置模板1已发送");
            $table->tinyInteger('status',false,true)->nullable()->default(0)->comment("奖品发送状态0未发送1已发送2补发成功");
            $table->timestamps();
            //索引
            $table->index(['user_id','main_id']);
            $table->index(['award_type','award_id']);
            $table->index('source_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('money_share_info');
    }
}
