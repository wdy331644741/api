<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTableAppStartpages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('app_startpages', function (Blueprint $table) {
            $table->string('img5')->after("img4")->nullable()->default("")->comment("iphoneX尺寸图片1125*2436");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('app_startpages', function (Blueprint $table) {
            $table->dropColumn(["img5"]);
        });
    }
}
