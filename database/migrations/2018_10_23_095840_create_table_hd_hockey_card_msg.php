<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableHdHockeyCardMsg extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hd_hockey_card_msg', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment('用户id');
            $table->string('msg')->default('')->comment('消息提示');
            $table->text('remark')->default('')->comment('备注信息');
            $table->tinyInteger('type')->default(0)->comment('类型1投资送卡消息，2兑换集卡现金消息，3兑换集卡实物消息');
            $table->timestamp('created_at')->nullable();
            $table->index(['user_id','type']);
            $table->comment = '曲棍球消息表';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('hd_hockey_card_msg');
    }
}
