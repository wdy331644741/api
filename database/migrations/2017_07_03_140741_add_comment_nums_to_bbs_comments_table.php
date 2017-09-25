<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCommentNumsToBbsCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bbs_comments', function (Blueprint $table) {
            $table->Integer('zan_num')->default(0)->comment('点赞数目');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bbs_comments', function (Blueprint $table) {
            $table->dropColumn(["zan_num"]);

        });
    }
}
