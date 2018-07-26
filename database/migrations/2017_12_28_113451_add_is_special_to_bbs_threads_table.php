<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsSpecialToBbsThreadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bbs_threads', function (Blueprint $table) {
            $table->tinyInteger('is_special')->default(0)->comment("特定帖 1：是，0：否");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bbs_threads', function (Blueprint $table) {
            $table->dropColumn('is_special');
        });
    }
}
