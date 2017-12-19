<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAmountShareInfo2017040401 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('amount_share_info', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id',false,true)->nullable()->default(0)->comment("用户id");
            $table->integer('main_id',false,true)->nullable()->default(0)->comment("主表id");
            $table->string('uuid',64)->nullable()->default(0)->comment("唯一id");
            $table->decimal('money',10,2)->nullable()->default(0)->comment("得到金额");
            $table->text('remark')->nullable()->default('')->comment("说明");
            $table->tinyInteger('status',false,true)->nullable()->default(0)->comment("奖品发送状态0失败1成功");
            $table->timestamps();
            //索引
            $table->index('user_id');
            $table->index('main_id');
            $table->index('money');
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
        Schema::drop('amount_share_info');
    }
}
