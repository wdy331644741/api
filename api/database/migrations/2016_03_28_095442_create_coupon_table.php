<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCouponTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coupon', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',64);//优惠券名称
            $table->text('desc');//介绍
            $table->string('file',128);//上传的优惠码文件
            $table->integer('created_at',false,true);//创建时间
            $table->integer('updated_at',false,true);//修改时间
            $table->tinyInteger('is_del',false,true);//是否删除0正常1删除
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('coupon');
    }
}
