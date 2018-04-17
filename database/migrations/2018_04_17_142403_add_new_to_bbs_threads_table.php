<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewToBbsThreadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('bbs_threads', function (Blueprint $table) {
            $table->tinyInteger('new')->default(0)->comment("最热贴 0 否 1 是");
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
        Schema::table('bbs_threads', function (Blueprint $table) {
            $table->dropColumn('new');
        });
    }
}
