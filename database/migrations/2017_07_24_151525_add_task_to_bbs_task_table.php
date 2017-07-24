<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTaskToBbsTaskTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('bbs_task', function (Blueprint $table) {

            $table->tinyInteger('task_group_id')->comment("任务组id");

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::table('bbs_tasks', function (Blueprint $table) {
            $table->dropColumn(["task_group_id"]);

        });
    }
}
