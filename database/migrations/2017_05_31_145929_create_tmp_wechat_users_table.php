<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTmpWechatUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tmp_wecaht_users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('openid')->index();
            $table->tinyInteger('sex')->default(0)->comment('用户的性别，值为1时是男性，值为2时是女性，值为0时是未知');
            $table->string('nick_name')->nullable()->default(NULL);
            $table->string('province')->nullable()->default(NULL);
            $table->string('city')->nullable()->default(NULL);
            $table->string('country')->nullable()->default(NULL);
            $table->string('headimgurl')->nullable()->default(NULL);
            $table->tinyInteger('iswin')->default(0)->comment("是否中奖：1：是，0：否(默认)");
            $table->tinyInteger('isdefault')->default(0)->comment("是否内定：1：是，0：否(默认)");
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
        Schema::drop('tmp_wecaht_users');
    }
}
