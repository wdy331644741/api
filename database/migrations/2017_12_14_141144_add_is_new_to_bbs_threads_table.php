<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsNewToBbsThreadsTable extends Migration
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

            $table->integer('is_new')->comment("是否是第一次发帖")->default(0)->index();
            //处理业务上回复处理成评论  做数据冗余  适应评论显示效果 0 评论 1 回复

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
            $table->dropColumn(["is_new"]);

        });
    }
}
