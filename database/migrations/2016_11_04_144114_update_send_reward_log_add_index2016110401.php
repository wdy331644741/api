<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateSendRewardLogAddIndex2016110401 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('send_reward_log', function (Blueprint $table) {
            $table->index(['activity_id', 'status']);
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
            $table->dropIndex(['activity_id', 'status']);
        });
    }
}
