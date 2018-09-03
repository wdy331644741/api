<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class HdJump extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hd_jump', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment('用户id');
            $table->string(' ')->default('')->comment('奖品名');
            $table->string('alias_name')->default('')->comment('别名');
            $table->string('uuid', 64)->default('')->comment('唯一id');
            $table->string('type')->default('')->comment('类型');
            $table->tinyInteger('status')->default(0)->comment('状态0失败1成功');
            $table->text('remark')->default('')->comment('备注');
            $table->timestamps();
            $table->index('user_id');
            $table->index('type');
            $table->comment = '跳一跳';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('hd_jump');
    }
}
