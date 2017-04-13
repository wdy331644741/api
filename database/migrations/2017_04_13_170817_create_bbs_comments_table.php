<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBbsCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bbs_comments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('from_user_id')->comment('from_user_id为0时，为官方回复消息');
            $table->integer('tid')->comment('帖子id');
            $table->string('content');
            $table->tinyInteger('isverify')->default(0)->comment('是否审核：0（默认）：否，1：是');
            $table->dateTime('verify_time')->nullable()->default(NULL)->comment('审核时间');
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
        Schema::drop('bbs_comments');
    }
}
