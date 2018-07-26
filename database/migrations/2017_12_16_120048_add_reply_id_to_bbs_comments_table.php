<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReplyIdToBbsCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('bbs_comments', function (Blueprint $table) {

            $table->integer('reply_id')->index();
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
        Schema::table('bbs_comments', function (Blueprint $table) {
            $table->dropColumn(["reply_id"]);

        });
    }
}
