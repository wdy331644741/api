<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBbsThreadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bbs_threads', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('type_id');
            $table->string('title')->nullable()->delault(NULL);
            $table->string('content',512);
            $table->tinyInteger('views')->default(0)->comment('浏览量');
            $table->tinyInteger('comment_num')->default(0)->comment('帖子评论数');
            $table->tinyInteger('istop')->default(0)->commnet('是否置顶 0（默认）：否 1：是');
            $table->tinyInteger('isgreat')->default(0)->comment('是否加精 0（默认）:否  1：是');
            $table->tinyInteger('ishot')->default(0)->comment('是否最热');
            $table->tinyInteger('isverify')->default(0)->commnet('是否审核 0:未审核,1:审核通过');
            $table->dateTime('verify_time')->nullable()->default(NULL)->commemt('审核时间');
            $table->index('user_id');
            $table->softDeletes();
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
        Schema::drop('bbs_threads');
    }
}
