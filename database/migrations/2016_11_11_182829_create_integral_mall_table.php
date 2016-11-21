<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIntegralMallTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('integral_mall', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('integral')->default(0);//出售的积分值
            $table->text('desc')->nullable();//说明
            $table->string('photo')->default('');//配图
            $table->integer('total_quantity',false,true)->default(0);//总量
            $table->integer('send_quantity',false,true)->default(0);//送出数量
            $table->integer('award_type',false,true)->default(0);//奖品类型
            $table->integer('award_id',false,true)->default(0);//奖品id
            $table->integer('user_quantity',false,true)->default(0);//用户兑换总量 0为不限
            $table->integer('priority',false,true)->default(0);//优先级
            $table->string('groups',64)->default('');//分组
            $table->timestamp('start_time')->nullable()->default(NULL);//开始时间
            $table->timestamp('end_time')->nullable()->default(NULL);//结束时间
            $table->tinyInteger('status',false,true)->default(0);//商品状态0未上线1上线
            $table->timestamp('release_time')->nullable()->default(NULL);//上线时间
            $table->timestamps();
        });
        Schema::table('integral_mall', function (Blueprint $table) {
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
        Schema::drop('integral_mall');
    }
}
