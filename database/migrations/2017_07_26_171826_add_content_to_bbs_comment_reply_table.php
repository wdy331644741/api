<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddContentToBbsCommentReplyTable extends Migration
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

            $table->text('content')->comment("内容");

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
            $table->dropColumn(["content"]);

        });
    }
}
