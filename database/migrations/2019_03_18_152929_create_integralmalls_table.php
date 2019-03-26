<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIntegralmallsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('integralmalls', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment('用户id')->index();
            $table->integer('credits')->comment('消耗积分');
            $table->string('itemCode',10)->nullable()->default(NULL)->comment("自有商品商品编码");
            $table->string('description')->nullable()->default(null)->comment('本次积分消耗的描述');
            $table->string('orderNum',20)->comment('兑吧订单号')->unique();
            $table->string('myOrderNum',20)->comment('自有订单号')->index();
            $table->string('type')->comment("兑换类型");
            $table->integer('facePrice')->nullable()->default(null)->comment('兑换商品的市场价值');
            $table->integer('actualPrice')->comment('此次兑换实际扣除开发者账户费用');
            $table->string('ip',20)->nullable()->default(null)->comment('用户ip');
            $table->tinyInteger('waitAudit')->nullable()->default(0)->comment('是否需要审核');
            $table->string('params')->nullable()->default(null)->comment('详情参数');
            $table->tinyInteger('status')->default(0)->comment("状态(0:失败，1：成功，2：处理中)");
            $table->string('remark')->nullable()->default(NULL)->comment("对方返回错误原因");
            $table->index(['orderNum','status']);
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
        Schema::drop('integralmalls');
    }
}
