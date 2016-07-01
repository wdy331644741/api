<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRedeemAwardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('redeem_award', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('award_type',false,true);
            $table->integer('award_id',false,true);
            $table->integer('number',false,true);
            $table->string('file_name');
            $table->timestamp('expire_time');
            $table->tinyInteger('status',false,true);//0未生成1生在生成2已生成
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
        Schema::drop('redeem_award');
    }
}
