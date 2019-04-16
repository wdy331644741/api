<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIntegralmallAddtimesLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('integralmall_addtimes_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment('用户id')->index();
            $table->integer('times')->comment('增加次数');
            $table->integer('actId')->comment('加次数的活动id');
            $table->tinyInteger('validType')->comment('增加次数类型');
            $table->string('bizId',40)->comment('uuid')->index();
            $table->tinyInteger('status')->default(0)->comment("状态(0:失败，1：成功)")->index();
            $table->string('remark')->nullable()->default(null)->comment('错误原因');
            $table->index(['bizId', 'status']);
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
        Schema::drop('integralmall_addtimes_logs');
    }
}
