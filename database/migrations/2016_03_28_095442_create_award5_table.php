<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAward5Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('award_5', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',64);//关联活动
            $table->integer('created_at',false,true);//创建时间
            $table->integer('updated_at',false,true);//修改时间
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('award_5');
    }
}
