<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateAward1 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('award_1', function (Blueprint $table) {
            $table->dropColumn('rate_increases_day');//加息时长（天数）删除
            $table->tinyInteger('rate_increases_time',false,true);//加息时长（新建可以为天数和月）新增
            $table->tinyInteger('project_duration_time',false,true);//项目期限时长  新增
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('award_1', function (Blueprint $table) {
            $table->dropColumn('rate_increases_time');//加息时长（天数）删除
            $table->tinyInteger('rate_increases_day',false,true);//加息时长（新建可以为天数和月）新增
            $table->dropColumn('project_duration_time');//项目期限时长  删除
        });
    }
}
