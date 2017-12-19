<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReasonAndCodeToBbsThreadsTable extends Migration
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
            $table->string('verify_label')->nullable()->default(NULL)->comment('审核分类信息');

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
            $table->dropColumn('verify_label');
        });
    }
}
