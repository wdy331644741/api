<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIntegralmallLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('integralmall_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment('用户id')->index();
            $table->integer('credits')->comment('消耗积分');
            $table->string('description')->nullable()->default(null)->comment('加积分消耗的描述');
            $table->string('orderNum',20)->comment('兑吧订单号')->index();
            $table->string('myOrderNum',20)->comment('自有订单号')->index();
            $table->string('type')->comment("加积分类型");
            $table->string('ip',20)->nullable()->default(null)->comment('用户ip');
            $table->tinyInteger('status')->default(0)->comment("状态(0:失败，1：成功,2:积分发送成功，记录失败")->index();
            $table->index(['orderNum', 'status']);
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
        Schema::drop('integralmall_logs');
    }
}
