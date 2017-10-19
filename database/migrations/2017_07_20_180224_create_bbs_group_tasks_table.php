<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBbsGroupTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bbs_group_tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('type_id')->comment('任务类型，1：每日任务，2：成就任务');
            $table->string('name');
            $table->string('alias_name')->nullable()->default(NULL);
            $table->text('tip');
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
        Schema::drop('bbs_group_tasks');
    }
}
