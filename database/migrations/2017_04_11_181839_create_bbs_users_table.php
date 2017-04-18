<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBbsUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bbs_users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('head_img')->nullable()->default(NULL);
            $table->string('phone');
            $table->string('nickname');
            $table->tinyInteger('isblack')->default(0)->comment('是否加黑名单 0(默认):否，1：是');
            $table->tinyInteger('black_type_id')->nullable()->default(NULL)->comment('拉黑理由');
            $table->dateTime('black_time')->nullable()->default(NULL)->comment('拉黑时间');
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
        Schema::drop('bbs_users');
    }
}
