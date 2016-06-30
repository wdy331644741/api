<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBannersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->increments('id');
            $table->string('position',32);//关联位置表
            $table->string('name',64);//名称
            $table->string('img_path',255);//图片地址
            $table->string('img_url',255);//图片跳转地址
            $table->timestamp('start');//开始时间
            $table->timestamp('end');//结束时间
            $table->text('desc',64);//描述
            $table->integer('sort',false,true);//排序值越大越靠前
            $table->tinyInteger('can_use',false,true);//是否可用1可用2不可用
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
        Schema::drop('banners');
    }
}
