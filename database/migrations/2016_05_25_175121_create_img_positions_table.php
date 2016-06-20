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
            $table->string('position',32);//关联位置表
            $table->string('nickname',32);//别名
            $table->integer('width',false,true);//图片宽度
            $table->integer('height',false,true);//图片高度
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
        Schema::drop('img_positions');
    }
}
