<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHdRatecouponFriendhelp extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hd_ratecoupon_friendhelp', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('f_userid')->comment('助力好友用户id');
            $table->integer('p_userid')->default(0)->comment('用户id');
            $table->float('amount')->comment('加息券数值');
            $table->string('alias_name')->default('')->comment('数值别名');
            $table->tinyInteger('status')->default(0)->comment('状态0失败1成功');
            $table->text('remark')->default('')->comment('备注');
            $table->timestamps();
            $table->index('p_userid');
            $table->comment = '抢加息券助力好友表';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('hd_ratecoupon_friendhelp');
    }
}
