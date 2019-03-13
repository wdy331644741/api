<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Friend30LimitTask extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //好友邀请3.0
        Schema::create('friend_30_limit_task', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->index()->comment('用户id');
            
            $table->string('alias_name')->comment('活动名称');
            $table->unsignedTinyInteger('status')->default(0)->comment('0领取 1任务完成');
            
            $table->string('date_str')->index()->comment('标示任务批次（当天）');
            $table->string('date_time_str')->comment('领取任务时间戳');
            $table->timestamp('limit_time')->nullable()->comment('过期时间');

            $table->float('user_prize', 8, 2)->comment('用户赏金');
            $table->integer('invite_user_id')->index()->comment('邀请用户id');
            $table->float('invite_prize', 8, 2)->comment('邀请d用户赏金');
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
        Schema::drop('friend_30_limit_task');
    }
}
