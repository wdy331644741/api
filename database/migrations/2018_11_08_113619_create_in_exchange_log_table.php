<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInExchangeLogTable extends Migration
{
    /**
     * Run the migrations.
     * 实物奖品发送记录
     * @return void
     */
    public function up()
    {
        Schema::create('in_exchange_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string("realname")->nullable()->comment("姓名");
            $table->string("user_id")->comment("用户id");
            $table->string("pid")->comment("id");
            $table->string("pname")->comment("奖品名称");
            $table->integer("type_id")->index();
            $table->integer("number")->default(0);
            $table->tinyInteger("is_real")->default(0);
            $table->string("phone")->nullable()->comment("手机号");
            $table->string("address")->nullable()->comment("地址");
            $table->string("status")->comment("状态");
            $table->string("track_num")->nullable()->comment("快递单号");
            $table->string("track_name")->nullable()->comment("快递名称");
            $table->softDeletes();
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
        Schema::drop('in_exchange_logs');
    }
}
