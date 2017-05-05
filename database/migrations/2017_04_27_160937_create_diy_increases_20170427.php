<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDiyIncreases20170427 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('diy_increases', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('increases_id',false,true)->default(0)->comment("用户属性表加息券id");
            $table->integer('user_id',false,true)->default(0)->comment("用户id");
            $table->integer('invite_user_id',false,true)->default(0)->comment("邀请的用户id");
            $table->integer('amount',false,true)->default(0)->comment("投资金额(不含小数)");
            $table->string('source')->default('')->comment("操作来源");
            $table->integer('number',false,true)->default(0)->comment("加息的值（需要除以10）");
            $table->timestamp('created_at')->default(NULL)->comment("创建时间");
            //索引
            $table->index('increases_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('diy_increases');
    }
}
