<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTUserIdToBbsCommentReplyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('bbs_comment_reply', function (Blueprint $table) {

            $table->integer('t_user_id')->index();
            //帖子id

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
        Schema::table('bbs_comment_reply', function (Blueprint $table) {
            $table->dropColumn(["t_user_id"]);

        });
    }
}
