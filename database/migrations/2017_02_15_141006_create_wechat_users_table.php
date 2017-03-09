<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wechat_users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('uid')->nullable()->default(NULL)->index();
            $table->string('openid')->index();
            $table->tinyInteger('sex')->default(0)->comment('用户的性别，值为1时是男性，值为2时是女性，值为0时是未知');
            $table->string('nick_name')->nullable()->default(NULL);
            $table->string('province')->nullable()->default(NULL);
            $table->string('city')->nullable()->default(NULL);
            $table->string('country')->nullable()->default(NULL);
            $table->string('headimgurl')->nullable()->default(NULL);
            $table->index(['uid','openid'],'uid_openid');
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
        Schema::dropIfExists('wechat_users');
    }
}
