<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBbsThreadRecordTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('bbs_thread_record', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('user_id')->comment('用户id')->index();
            $table->string('tid')->comment('帖子id')->index();
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
        //
        Schema::drop('bbs_thread_record');
    }
}
