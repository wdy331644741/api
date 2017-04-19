<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsreadToBbsPmTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('bbs_pms', function (Blueprint $table) {
            $table->tinyInteger('isread')->default(0)->comment('0 未读 1 已读');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::table('bbs_pms', function (Blueprint $table) {
            $table->dropColumn('isread');
        });
    }
}
