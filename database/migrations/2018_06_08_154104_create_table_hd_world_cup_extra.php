<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableHdWorldCupExtra extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hd_world_cup_extra', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment('用户id');
            $table->integer('f_userid')->comment('邀请用户id');
            $table->unsignedSmallInteger('number')->default(0)->comment('额外球数');
            $table->tinyInteger('type')->default(0)->comment('1注册绑卡 2首次出借2000');
            $table->text('remark')->nullable()->default('')->comment("备注");
            $table->timestamps();
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('hd_world_cup_extra');
    }
}
