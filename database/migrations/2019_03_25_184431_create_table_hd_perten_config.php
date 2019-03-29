<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableHdPertenConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hd_perten_config', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ultimate_award', 32)->default('')->comment('终极大奖名');
            $table->string('ultimate_img1', 128)->default('')->comment('终极大奖图片(小)');
            $table->string('ultimate_img2', 128)->default('')->comment('终极大奖图片(大)');
            $table->string('first_award', 32)->default('')->comment('一马当先奖名');
            $table->string('first_img1', 128)->default('')->comment('一马当先奖图片(小)');
            $table->string('first_img2', 128)->default('')->comment('一马当先奖图片(大)');
            $table->string('sunshine_award', 32)->default('')->comment('阳光普照奖名');
            $table->string('sunshine_img1', 128)->default('')->comment('阳光普照奖图片(小)');
            $table->string('sunshine_img2', 128)->default('')->comment('阳光普照奖图片(大)');
            $table->unsignedInteger("numbers",false,true)->default(0)->comment('码数量');
            $table->timestamp('start_time')->nullable()->comment('开始时间');
            $table->tinyInteger('insert_status')->default(0)->comment('状态0未开始 1导入中 2已完成');
            $table->tinyInteger('status')->default(0)->comment('状态0下线 1上线');
            $table->string('ultimate_pc1', 128)->default('')->comment('pc终极大奖图片(小)');
            $table->string('ultimate_pc2', 128)->default('')->comment('pc终极大奖图片(大)');
            $table->string('first_pc1', 128)->default('')->comment('pc一马当先奖图片(小)');
            $table->string('first_pc2', 128)->default('')->comment('pc一马当先奖图片(大)');
            $table->string('sunshine_pc1', 128)->default('')->comment('pc阳光普照奖图片(小)');
            $table->string('sunshine_pc2', 128)->default('')->comment('pc阳光普照奖图片(大)');
            $table->string('award_text')->default('')->comment('奖品专场文字');
            $table->string('ultimate_rule',2000)->default('')->comment('终极大奖规则');
            $table->string('first_rule', 2000)->default('')->comment('一马当先奖规则');
            $table->string('sunshine_rule', 2000)->default('')->comment('阳光普照奖规则');
            $table->text('activity_rule')->default('')->comment('活动规则');
            $table->decimal('ultimate_price')->unsigned()->default('0.00')->comment('终极大奖价值');
            $table->decimal('first_price')->unsigned()->default('0.00')->comment('一马当先奖价值');
            $table->decimal('sunshine_price')->unsigned()->default('0.00')->comment('阳光普照奖价值');
            $table->decimal('guess_award',10, 2)->unsigned()->default(0)->comment('天天猜瓜分金额');
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
        Schema::drop('hd_perten_config');
    }
}
