<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTUserIdToBbsThreadCollectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('bbs_thread_collections', function (Blueprint $table) {

            $table->Integer('t_user_id')->comment("帖子用户id");

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
        Schema::table('bbs_thread_collections', function (Blueprint $table) {
            $table->dropColumn(["t_user_id"]);

        });
    }
}
