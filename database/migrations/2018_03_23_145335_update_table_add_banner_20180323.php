<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTableAddBanner20180323 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->string('url_ios',255)->default("")->comment("ios跳转地址，url为安卓跳转地址")->after("url");//ios跳转地址，url为安卓跳转地址
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('url_ios');//ios跳转地址，url为安卓跳转地址
        });
    }
}
