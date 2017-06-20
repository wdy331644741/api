<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCommentIdToBbsPmsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bbs_pms', function (Blueprint $table) {
            $table->integer('comment_id')->nullable()->default(NULL)->comment('评论id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bbs_pms', function (Blueprint $table) {
            $table->dropColumn('comment_id');
        });
    }
}
