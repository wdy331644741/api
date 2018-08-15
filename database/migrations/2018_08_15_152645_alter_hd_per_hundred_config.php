<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterHdPerHundredConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hd_per_hundred_config', function (Blueprint $table) {
            $table->string('ultimate_pc1', 128)->default('')->comment('pc终极大奖图片(小)');
            $table->string('ultimate_pc2', 128)->default('')->comment('pc终极大奖图片(大)');
            $table->string('first_pc1', 128)->default('')->comment('pc一马当先奖图片(小)');
            $table->string('first_pc2', 128)->default('')->comment('pc一马当先奖图片(大)');
            $table->string('last_pc1', 128)->default('')->comment('pc一锤定音奖图片(小)');
            $table->string('last_pc2', 128)->default('')->comment('pc一锤定音奖图片(大)');
            $table->string('sunshine_pc1', 128)->default('')->comment('pc阳光普照奖图片(小)');
            $table->string('sunshine_pc2', 128)->default('')->comment('pc阳光普照奖图片(大)');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
//        Schema::drop('hd_per_hundred_config');
        Schema::table('hd_per_hundred_config', function (Blueprint $table) {
            //
            $table->dropColumn(["ultimate_pc1", "ultimate_pc2", "first_pc1","first_pc2","last_pc1","last_pc2","sunshine_pc1","sunshine_pc2"]);
        });
    }
}
