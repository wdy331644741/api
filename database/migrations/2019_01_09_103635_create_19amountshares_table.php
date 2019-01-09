<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Create19amountsharesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hd_19amountshares', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('share_user_id')->index();
            $table->string('share_phone');
            $table->integer('user_id')->index();
            $table->string('phone');
            $table->decimal('amount')->default(0)->index();
            $table->tinyInteger('user_status')->default(3)->index()->comment('1新手未注册,2注册未绑卡,3老用户');
            $table->tinyInteger('receive_status')->index()->comment('1未领取（在途奖励）,2已领取,3领取失败');
            $table->integer('date')->index();
            $table->string('remark')->comment('返回结果记录');
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
        Schema::drop('19amountshares');
    }
}
