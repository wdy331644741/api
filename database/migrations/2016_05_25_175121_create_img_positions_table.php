<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImgPositionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('img_positions', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('position',false,true);//关联位置表
            $table->integer('width',false,true);//图片宽度
            $table->integer('height',false,true);//图片高度
            $table->integer('created_at',false,true);//添加时间
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('img_positions');
    }
}
