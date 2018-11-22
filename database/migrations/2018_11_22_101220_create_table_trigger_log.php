<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableTriggerLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trigger_log', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment('用户id');
            $table->string('trigger_type')->comment('推送类型');
            $table->text('info')->default('')->comment('触发信息');
            $table->string('day')->default('')->comment('日期2018-11-17');
            $table->timestamp('created_at')->nullable();
            $table->index('user_id');
            $table->index('trigger_type');
            $table->index('day');
            $table->comment = '触发消息记录表';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('trigger_log');
    }
}
