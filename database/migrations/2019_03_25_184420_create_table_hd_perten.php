<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableHdPerten extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hd_perten', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment('用户id');
            $table->unsignedInteger('draw_number')->comment('抽奖号码');
            $table->unsignedTinyInteger('period')->comment('期数id');
            $table->string('award_name')->default('')->comment('奖品名');
            $table->string('alias_name')->default('')->comment('别名');
            $table->string('uuid', 64)->default('')->comment('唯一id');
            $table->string('type')->default('')->comment('类型');
            $table->tinyInteger('status')->default(0)->comment('状态0失败1成功');
            $table->text('remark')->default('')->comment('备注');
            $table->timestamps();
            $table->index('user_id');
            $table->comment = '逢百中奖表';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('hd_perten');
    }
}
