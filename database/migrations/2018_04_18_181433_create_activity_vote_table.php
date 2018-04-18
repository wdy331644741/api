<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityVoteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activity_vote', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id',false,true)->nullable()->default(0)->comment("用户id");
            $table->string('vote')->nullable()->default('')->comment("投票");
            $table->integer('rank')->nullable()->default(1)->comment("第几位支持者");
            $table->text('remark')->nullable()->default('')->comment("说明");
            $table->tinyInteger('status',false,true)->nullable()->default(0)->comment("奖品发送状态0失败1成功");
            $table->timestamps();
            //索引
            $table->index('user_id');
            $table->index('vote');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('activity_vote');
    }
}
