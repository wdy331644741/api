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
            $table->tinyInteger('award_type',false,true);//奖品类型 1、加息券2、直抵红包3、百分比红包4、体验金5、用户积分6、实物7、优惠券
            $table->integer('award_id',false,true);//奖品关联id
            $table->integer('created_at',false,true);//创建时间
            $table->integer('updated_at',false,true);//修改时间
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
