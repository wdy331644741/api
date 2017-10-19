<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomecolToBbsThreadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bbs_threads', function (Blueprint $table) {
            $table->string('title')->change();
            $table->tinyInteger('isofficial')->default(0)->comment('是否为官方发帖');
            $table->integer('collection_num')->default(0)->comment('收藏数');
            $table->integer('zan_num')->default(0)->comment('帖子点赞数');
            $table->text('video_code')->nullable()->default(NULL)->comment('视频代码');
            $table->index('isofficial');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bbs_threads', function (Blueprint $table) {
            $table->dropColumn(['isofficial','collection_num','zan_num','video_code']);
        });
    }
}
