<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableHdAmountShare20170703 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hd_amount_share', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id',false,true)->nullable()->default(0)->comment("用户id");
            $table->string('identify')->nullable()->default('')->comment("唯一识别码");
            $table->decimal('investment_amount',10,2)->default(0)->comment("投资金额");
            $table->integer('period',false,true)->default(0)->comment("投资标期");
            $table->string('multiple')->default(NULL)->comment("现金分享倍数0.0005为一个月标，0.001为三个月标，0.0015为6个月以上标");
            $table->integer('level')->default(1)->comment("用户vip等级");
            $table->decimal('total_money',10,2)->nullable()->default(0)->comment("总金额");
            $table->decimal('use_money',10,2)->nullable()->default(0)->comment("分享金额");
            $table->integer('total_num',false,true)->nullable()->default(0)->comment("红包总数");
            $table->integer('receive_num',false,true)->nullable()->default(0)->comment("领取个数");
            $table->decimal('min',10,2)->default(0)->comment("最小值");
            $table->decimal('max',10,2)->default(0)->comment("最大值");
            $table->integer('week',false,true)->default(0)->comment("第几周首次领取");
            $table->timestamp('start_time')->nullable()->default(NULL)->comment("开始时间");
            $table->timestamp('end_time')->nullable()->default(NULL)->comment("结束时间");
            $table->text('uri')->default('')->comment("分享的uri");
            $table->tinyInteger('status',false,true)->nullable()->default(0)->comment("参与状态0未满1已满");
            $table->tinyInteger('award_status',false,true)->nullable()->default(0)->comment("本人领奖状态0未领取1已领取");
            $table->timestamps();
            //索引
            $table->index('user_id');
            $table->index('identify');
            $table->index('status');
            $table->index('award_status');
            $table->index('week');
        });
        Schema::table('hd_amount_share', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('hd_amount_share');
    }
}
