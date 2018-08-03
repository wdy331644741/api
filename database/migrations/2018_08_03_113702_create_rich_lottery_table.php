<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRichLotteryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rich_lottery', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment("用户id");
            $table->decimal('amount',10,2)->default(0)->comment("值");
            $table->string('award_name')->default('')->comment("奖品名");
            $table->string('uuid', 64)->default('')->comment("唯一id对应用户中心");
            $table->tinyInteger('type')->comment("奖品类型");
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
        Schema::drop('rich_lottery');
    }
}
