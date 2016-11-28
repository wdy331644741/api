<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFlowRechargeLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('flow_recharge_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('order_id')->nullable()->default(NULL);
            $table->string('corder_id');
            $table->string('phone');
            $table->integer('spec');
            $table->string('scope')->comment('全国：nation，省内：province');
            $table->tinyInteger('status')->default(0)->comment('0:充值失败，1：充值成功');
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
        Schema::drop('flow_recharge_logs');
    }
}
