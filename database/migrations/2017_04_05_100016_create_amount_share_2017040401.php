<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAmountShare2017040401 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('amount_share', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id',false,true)->nullable()->default(0)->comment("用户id");
            $table->string('identify')->nullable()->default('')->comment("唯一识别码");
            $table->decimal('total_money',10,2)->nullable()->default(0)->comment("总金额");
            $table->decimal('use_money',10,2)->nullable()->default(0)->comment("分享金额");
            $table->integer('total_num',false,true)->nullable()->default(0)->comment("红包总数");
            $table->integer('receive_num',false,true)->nullable()->default(0)->comment("领取个数");
            $table->decimal('min',10,2)->default(0)->comment("最小值");
            $table->decimal('max',10,2)->default(0)->comment("最大值");
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
        });
        Schema::table('amount_share', function (Blueprint $table) {
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
        Schema::drop('amount_share');
    }
}
