<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateHdPerHundredConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hd_per_hundred_config', function (Blueprint $table) {
            $table->string('award_text')->default('')->comment('奖品专场文字');
            $table->string('ultimate_rule',2000)->default('')->comment('终极大奖规则');
            $table->string('first_rule', 2000)->default('')->comment('一马当先奖规则');
            $table->string('last_rule', 2000)->default('')->comment('一锤定音规则');
            $table->string('sunshine_rule', 2000)->default('')->comment('阳光普照奖规则');
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
        Schema::table('hd_per_hundred_config', function (Blueprint $table) {
            //
            $table->dropColumn(["award_text", "ultimate_rule", "first_rule","last_rule","sunshine_rule","activity_rule"]);
        });
    }
}
