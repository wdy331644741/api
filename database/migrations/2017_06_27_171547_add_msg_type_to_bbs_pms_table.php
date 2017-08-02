<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMsgTypeToBbsPmsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bbs_pms', function (Blueprint $table) {
            $table->tinyInteger('msg_type')->index()->comment('1:系统，2:互动');
            $table->index('msg_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bbs_pms', function (Blueprint $table) {
            $table->dropColumn('msg_type');
        });
    }
}
