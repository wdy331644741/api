<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTypeToBbsPmsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bbs_pms', function (Blueprint $table) {
            $table->tinyInteger('type')->comment('消息类型');
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
            $table->dropColumn('type');
        });
    }
}
