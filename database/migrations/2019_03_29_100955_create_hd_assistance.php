<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHdAssistance extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hd_assistance', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('group_user_id')->default(0)->comment('团长id');
            $table->integer('group_ranking')->nullable()->comment('团排名');
            $table->integer('pid')->default(0)->comment('团长记录id');
            $table->integer('user_id')->default(0)->comment('团员id');
            $table->tinyInteger('group_num')->default(0)->comment('团员人数');
            $table->tinyInteger('award')->default(0)->comment('奖品');
            $table->tinyInteger('receive_num')->default(0)->comment('领取人数');
            $table->tinyInteger('receive_status')->default(0)->comment('领取状态0未领取1成功');
            $table->tinyInteger('status')->default(0)->comment('实名状态0未实名1已实名');
            $table->integer('day')->default(0)->comment('天20190101');
            $table->timestamp('complete_time')->nullable()->comment('满团时间');
            $table->timestamps();
            $table->index('group_user_id');
            $table->index('group_ranking');
            $table->index('pid');
            $table->index('user_id');
            $table->index('award');
            $table->index('receive_status');
            $table->index('status');
            $table->index('day');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('hd_assistance');
    }
}
