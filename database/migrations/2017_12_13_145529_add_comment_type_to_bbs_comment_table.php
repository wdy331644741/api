<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCommentTypeToBbsCommentTable extends Migration
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

            $table->integer('comment_type')->comment("回复类型")->default(0)->index();
            //处理业务上回复处理成评论  做数据冗余  适应评论显示效果

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
            $table->dropColumn(["comment_type"]);

        });
    }
}
