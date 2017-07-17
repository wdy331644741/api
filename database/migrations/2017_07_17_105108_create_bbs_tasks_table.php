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
            $table->tinyInteger('task_type')->comment('任务类型，1：每日任务，2：成就任务');
            $table->string('name')->comment('任务名称');
            $table->integer('number');
            $table->tinyInteger('trigger_type');
            $table->string('remark')->nullable()->default(NULL);
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
        Schema::drop('bbs_tasks');
    }
}
