<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableHdPerHundredConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hd_per_hundred_config', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ultimate_award', 32)->default('')->comment('终极大奖名');
            $table->string('ultimate_img1', 128)->default('')->comment('终极大奖图片(小)');
            $table->string('ultimate_img2', 128)->default('')->comment('终极大奖图片(大)');
            $table->string('first_award', 32)->default('')->comment('一马当先奖名');
            $table->string('first_img1', 128)->default('')->comment('一马当先奖图片(小)');
            $table->string('first_img2', 128)->default('')->comment('一马当先奖图片(大)');
            $table->string('last_award', 32)->default('')->comment('一锤定音奖名');
            $table->string('last_img1', 128)->default('')->comment('一锤定音奖图片(小)');
            $table->string('last_img2', 128)->default('')->comment('一锤定音奖图片(大)');
            $table->string('sunshine_award', 32)->default('')->comment('阳光普照奖名');
            $table->string('sunshine_img1', 128)->default('')->comment('阳光普照奖图片(小)');
            $table->string('sunshine_img2', 128)->default('')->comment('阳光普照奖图片(大)');
            $table->integer("numbers",false,true)->default(0)->comment('码数量');
            $table->timestamp('start_time')->nullable()->comment('开始时间');
            $table->tinyInteger('insert_status')->default(0)->comment('状态0未开始 1导入中 2已完成');
            $table->tinyInteger('status')->default(0)->comment('状态0下线 1上线');
            $table->text('remark')->default('')->comment('备注');
            $table->timestamps();
            $table->comment = '逢百抽奖活动配置';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('hd_per_hundred_config');
    }
}
