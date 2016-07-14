<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateAward2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('award_2', function (Blueprint $table) {
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
        Schema::table('award_2', function (Blueprint $table) {
            $table->dropColumn('project_duration_time');//项目期限时长  删除
        });
    }
}
