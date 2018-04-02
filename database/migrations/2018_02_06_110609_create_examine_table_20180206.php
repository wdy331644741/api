<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExamineTable20180206 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('examine', function (Blueprint $table) {
            $table->increments('id');
            $table->string('versions',32)->default('')->comment('版本号');
            $table->string('company_name',64)->default('')->comment('现公司名称显示');
            $table->tinyInteger('disclosure_click',false,true)->default(0)->comment('信息披露是否可点击0否，1是');
            $table->tinyInteger('bottom_click', false,true)->default(0)->comment('底部信息区是否可点击0否，1是');
            $table->tinyInteger('novice_click',false,true)->default(0)->comment('新手指引图标是否可点');
            $table->string('home_banner')->default('')->comment('首页上线活动图');
            $table->string('discover_banner')->default('')->comment('发现页上线活动图');
            $table->tinyInteger('status')->default(0)->comment('状态0禁用1启用');
            $table->timestamps();
            $table->comment = 'ios过审配置';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('examine');
    }
}
