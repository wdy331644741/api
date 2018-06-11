<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableHdWorldCupSupport extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hd_world_cup_support', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment('用户id');
            $table->integer('world_cup_config_id')->comment('配置表id');
            $table->unsignedSmallInteger('number')->default(0)->comment('支持次数');
            $table->text('remark')->nullable()->default('')->comment("备注");
            $table->timestamps();
            $table->index('user_id');
            $table->index('world_cup_config_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('hd_world_cup_support');
    }
}
