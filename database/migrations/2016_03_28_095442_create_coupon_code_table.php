<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCouponCodeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coupon_code', function (Blueprint $table) {
            $table->increments('id');
            $table->Integer('coupon_id',false,true);//关联优惠券信息主表id
            $table->string('code',255);//优惠码
            $table->tinyInteger('is_use',false,true);//是否可用0可用1已使用
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('coupon_code');
    }
}
