<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('images', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('type',false,true);//1、pc端2、手机端
            $table->tinyInteger('position',false,true);//关联位置表
            $table->string('img_path',255);//图片地址
            $table->string('img_url',255);//图片跳转地址
            $table->integer('width',false,true);//图片宽度
            $table->integer('height',false,true);//图片高度
            $table->integer('start',false,true);//开始时间
            $table->integer('end',false,true);//结束时间
            $table->integer('sort',false,true);//排序值越大越靠前
            $table->integer('created_at',false,true);//添加时间
            $table->integer('updated_at',false,true);//修改时间
            $table->tinyInteger('can_use',false,true);//是否可用1可用2不可用
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('images');
    }
}
