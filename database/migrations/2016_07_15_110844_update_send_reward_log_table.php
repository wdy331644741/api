<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateSendRewardLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('send_reward_log', function (Blueprint $table) {
            $table->integer('award_id',false,true);//活动id新增
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('send_reward_log', function (Blueprint $table) {
            $table->dropColumn('award_id');//活动id删除
        });
    }
}
