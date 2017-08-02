<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBbsTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bbs_tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('group_id')->comment('组任务id');
            $table->string('name')->comment('任务名称');
            $table->integer('number')->comment('触发条件');
            $table->string('task_mark')->index()->comment('任务唯一性标志');
            $table->tinyInteger('trigger_type')->comment('触发节点');//publishThread //publishComment //commentZan //threadZan //threadCollections
            $table->tinyInteger('award_type')->comment('奖励类型');//1 体验金
            $table->integer('award')->comment('奖励数量');//奖励数量
            $table->tinyInteger('frequency')->default(0)->comment('发将频次，0:不限，1：仅一次，2：一天一次');
            $table->tinyInteger('enable')->default(0)->comment("启用 0 未启用 1 启用");
            $table->string('remark')->nullable()->default(NULL);
            $table->timestamps();
            $table->index('group_id');
            $table->index('trigger_type');
            $table->index('enable');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('bbs_tasks');
    }
}
