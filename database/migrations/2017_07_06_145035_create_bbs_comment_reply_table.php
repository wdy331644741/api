<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBbsCommentReplyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bbs_comment_reply', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('comment_id')->index()->comment('根评论id');
            $table->integer('reply_id');
            $table->string('reply_type');
            $table->integer('from_id');
            $table->integer('to_id');
            $table->tinyInteger('is_verify');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('bbs_comment_reply');
    }
}
