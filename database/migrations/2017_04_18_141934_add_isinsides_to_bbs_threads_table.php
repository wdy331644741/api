<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsinsidesToBbsThreadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bbs_threads', function (Blueprint $table) {
            $table->tinyInteger('isinside')->default(0)->comment('是否内部发帖 0（默认）:否，1：是');
            $table->string('cover')->nullable()->default(NULL);
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
            $table->dropColumn(['isinside','cover']);
        });
    }
}
