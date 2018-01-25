<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class HdRatecouponFirend extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hd_ratecoupon_friend', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('f_userid')->comment('助力好友用户id');
            $table->integer('p_userid')->comment('用户id');
            $table->float('total_amount')->comment('累计加息券数值');
            $table->timestamps();
            $table->index('p_userid');
            $table->index('f_userid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('hd_ratecoupon_friend');
    }
}
