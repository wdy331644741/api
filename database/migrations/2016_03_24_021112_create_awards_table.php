<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAwardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('awards', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('activity_id',false,true);//关联活动
            $table->tinyInteger('award_type',false,true);//奖品类型 1、加息券2、直抵红包、百分比红包3、体验金4、用户积分5、实物6、优惠券
            $table->integer('award_id',false,true);//奖品关联id
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
        Schema::drop('awards');
    }
}
