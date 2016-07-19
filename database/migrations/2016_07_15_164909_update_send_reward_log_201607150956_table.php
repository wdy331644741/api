<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateSendRewardLog201607150956Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('send_reward_log', function (Blueprint $table) {
            $table->dropColumn('created_at');
        });
        //
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('send_reward_log', function (Blueprint $table) {
            $table->timestamp('created_at');
        });       
    }
}
