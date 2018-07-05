<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableHdWorldCupConfig20180607 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hd_world_cup_config', function (Blueprint $table) {
            $table->increments('id');
            $table->string('team')->nullable()->default('')->comment("球队");
            $table->tinyInteger('number',false,true)->nullable()->default(0)->comment("进球数");
            $table->text('remark')->nullable()->default('')->comment("备注");
            $table->timestamps();
        });
        Schema::table('hd_world_cup_config', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('hd_world_cup_config');
    }
}
