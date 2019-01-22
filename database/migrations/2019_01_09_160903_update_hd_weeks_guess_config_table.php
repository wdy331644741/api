<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateHdWeeksGuessConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hd_weeks_guess_config', function (Blueprint $table) {
            $table->text('activity_rule')->default('')->comment('活动规则');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hd_weeks_guess_config', function (Blueprint $table) {
            $table->dropColumn('activity_rule');
        });
    }
}
