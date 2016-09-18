<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateSendRewardLogAddIndex2016091801 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('send_reward_log', function (Blueprint $table) {
            $table->index(['user_id', 'activity_id']);
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
            $table->dropIndex(['user_id', 'activity_id']);
        });
    }
}
