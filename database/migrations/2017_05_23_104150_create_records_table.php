<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('records', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->default(0)->comment("用户ID");
            $table->string('host')->default('')->comment("主机名");
            $table->string('path')->default('')->comment("路径");
            $table->string('method')->default('')->comment("请求方式");
            $table->string('query')->default('')->comment("get");
            $table->text('post')->default('')->comment("post");
            $table->string('ip')->default('')->comment("ip");
            $table->string('user_agent')->default('')->comment("浏览器信息");
            $table->timestamps();
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
        Schema::drop('records');
    }
}
