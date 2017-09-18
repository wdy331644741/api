<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableHdAmountShareElevenInfo20170918 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hd_amount_share_eleven_info', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id',false,true)->nullable()->default(0)->comment("用户id");
            $table->integer('main_id',false,true)->nullable()->default(0)->comment("主表id");
            $table->string('uuid',64)->nullable()->default(0)->comment("唯一id");
            $table->tinyInteger('is_new',false,true)->nullable()->default(0)->comment("是否是新用户0否，1是，2为分享人最后得到的金额");
            $table->decimal('money',10,2)->nullable()->default(0)->comment("得到金额");
            $table->text('remark')->nullable()->default('')->comment("说明");
            $table->tinyInteger('status',false,true)->nullable()->default(0)->comment("奖品发送状态0失败1成功");
            $table->timestamps();
            //索引
            $table->index('user_id');
            $table->index('main_id');
            $table->index('is_new');
            $table->index('money');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('hd_amount_share_eleven_info');
    }
}
