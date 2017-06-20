<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSignInSystemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sign_in_system', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment("用户id");
            $table->decimal('amount',10,2)->default(0)->comment("得到金额");
            $table->string('multiple')->default('1')->comment("倍数");
            $table->string('multiple_card')->default('0')->comment("倍数卡");
            $table->string('award_name')->default('')->comment("奖品名");
            $table->string('uuid', 64)->default('')->comment("唯一id对应刘奇那边");
            $table->tinyInteger('type')->comment("7:现金 2:红包");
            $table->tinyInteger('status')->default(0)->comment("状态：0失败，1成功");
            $table->string('ip', 15)->default('')->comment("ip地址");
            $table->string('user_agent', 256)->default('')->comment("浏览器标示");
            $table->text('remark')->default('')->comment("备注");
            $table->timestamps();
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('sign_in_system');
    }
}
