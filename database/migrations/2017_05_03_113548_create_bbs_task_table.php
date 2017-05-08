<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBbsTaskTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('bbs_task', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('task_type')->comment('任务类型');
            $table->integer('award')->comment('奖励奖励金');
            $table->dateTime('award_time')->comment('领取奖励时间');
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
        //
        Schema::drop('bbs_task');
    }
}
