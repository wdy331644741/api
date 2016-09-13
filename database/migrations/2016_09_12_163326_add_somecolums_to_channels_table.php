<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomecolumsToChannelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->tinyInteger('coop_status')->default(0)->comment('0:正常，1：暂停拉新，2:暂停合作，3:渠道归并');
            $table->string('classification')->comment('----:不限制，CPC:按点击计费，CPD:按天计费，CPT:按时间计费,CPA:按行为计费，CPS:按销售计费');
            $table->tinyInteger('is_abandoned')->default(0)->comment('0:否，1:是');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn('coop_status');
            $table->dropColumn('classification');
            $table->dropColumn('is_abanded');
        });
    }
}
