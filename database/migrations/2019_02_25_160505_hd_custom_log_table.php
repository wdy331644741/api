<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class HdCustomLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hd_custom_log', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment('用户id');
            $table->string('award_name')->default('')->comment('奖品名');
            $table->string('uuid', 64)->default('');
            $table->string('type')->default('')->comment('类型');
            $table->tinyInteger('status')->default(0)->comment('状态0失败1成功');
            $table->text('remark')->default('')->comment('备注');
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
        Schema::drop('hd_custom_log');
    }
}
