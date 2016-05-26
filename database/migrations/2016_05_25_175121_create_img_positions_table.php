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
        Schema::drop('img_positions');
    }
}
