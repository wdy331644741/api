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
            $table->integer('user_id',false,true)->default(0)->comment("用户id");
            $table->string('source')->default('')->comment("来源");
            $table->integer('number',false,true)->default(0)->comment("加息的值（需要除以10）");
            $table->timestamp('created_at')->nullable()->default(NULL)->comment("创建时间");
            //索引
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
