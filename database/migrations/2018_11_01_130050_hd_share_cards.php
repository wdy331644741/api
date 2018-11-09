<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class HdShareCards extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hd_share_cards', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment('用户id');
            $table->string('share')->default('')->comment('分享thing');
            $table->string('alias_name')->default('')->comment('别名');
            $table->string('encry')->default('')->comment('uid+share+time() md5');
            $table->integer('receive_user')->comment('接收用户id');
            $table->string('uuid', 64)->default('')->comment('唯一id');
            $table->string('type')->default('')->comment('类型');
            $table->tinyInteger('status')->default(0)->comment('状态 0失败1成功');
            $table->text('remark')->default('')->comment('备注');
            $table->timestamps();
            $table->index('user_id');
            $table->index('share');
            $table->index('encry');
            $table->comment = '娃娃机分享';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('hd_share_cards');
    }
}
