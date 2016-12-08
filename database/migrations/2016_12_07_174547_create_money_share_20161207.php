<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMoneyShare20161207 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('money_share', function (Blueprint $table) {
            $table->increments('id');
            $table->string('blessing')->nullable()->default('')->comment("祝福语");
            $table->integer('user_id',false,true)->nullable()->default(0)->comment("用户id");
            $table->string('user_name',32)->nullable()->default('')->comment("用户名");
            $table->string('identify')->nullable()->default('')->comment("唯一识别码");
            $table->integer('award_type',false,true)->nullable()->default(0)->comment("奖品类型");
            $table->integer('award_id',false,true)->nullable()->default(0)->comment("奖品id");
            $table->integer('total_money',false,true)->nullable()->default(0)->comment("总金额");
            $table->integer('use_money',false,true)->nullable()->default(0)->comment("使用金额");
            $table->integer('total_num',false,true)->nullable()->default(0)->comment("红包总数");
            $table->integer('receive_num',false,true)->nullable()->default(0)->comment("领取个数");
            $table->integer('min',false,true)->nullable()->default(0)->comment("最小值");
            $table->integer('max',false,true)->nullable()->default(0)->comment("最大值");
            $table->timestamp('start_time')->nullable()->default(NULL)->comment("开始时间");
            $table->timestamp('end_time')->nullable()->default(NULL)->comment("结束时间");
            $table->tinyInteger('status',false,true)->default(0)->comment("状态0未上线1上线");
            $table->timestamps();
            //索引
            $table->index('identify');
        });
        Schema::table('money_share', function (Blueprint $table) {
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
        Schema::drop('money_share');
    }
}
