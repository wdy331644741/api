<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateSendRewardLog201607150957Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('send_reward_log', function (Blueprint $table) {
            $table->timestamps();
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
            $table->dropColumn('created_at', 'updated_at');
        });
        //
    }
}
