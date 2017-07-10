<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTUserIdToBbsCommentZansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bbs_comment_zans', function (Blueprint $table) {

            $table->Integer('c_user_id')->comment("回复用户id");

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bbs_comment_zans', function (Blueprint $table) {
            $table->dropColumn(["c_user_id"]);

        });
    }
}
