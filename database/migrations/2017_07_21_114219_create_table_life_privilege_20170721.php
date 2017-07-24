<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableLifePrivilege20170721 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('life_privilege', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id',false,true)->default(NULL)->comment("充值的用户id");
            $table->string('phone')->default('')->comment("充值手机号");
            $table->string('order_id')->default('')->comment("订单id（生成的和殴飞对接的id）");
            $table->decimal('amount',10,2)->default(0)->comment("网利宝扣除金额");
            $table->decimal('amount_of',10,2)->default(0)->comment("殴飞接口金额（订单金额）");
            $table->string('name')->default(NULL)->comment("商品名");
            $table->tinyInteger('type',false,true)->default(0)->comment("商品类型1话费2流量");
            $table->tinyInteger('operator_type',false,true)->default(0)->comment("运营商类型1移动2联通3电信");
            $table->tinyInteger('debit_status',false,true)->default(0)->comment("网利宝扣款状态0未扣款 1已扣款");
            $table->tinyInteger('order_status',false,true)->default(0)->comment("订单状态0未充值 1正在充值 2充值失败 3充值成功");
            $table->text('remark')->default(NULL)->comment("用户扣钱备注");
            $table->text('remark_of')->default(NULL)->comment("殴飞接口备注");
            $table->timestamps();
            //索引
            $table->index('user_id');
            $table->index('phone');
            $table->index('order_id');
            $table->index('amount');
            $table->index('name');
            $table->index('type');
            $table->index('operator_type');
            $table->index('debit_status');
            $table->index('order_status');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('life_privilege');
    }
}
