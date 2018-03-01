<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTUserIdToBbsCommentsTable extends Migration
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
        chema::table('bbs_comments', function (Blueprint $table) {
            $table->dropColumn(["t_user_id"]);

        });
    }
}
