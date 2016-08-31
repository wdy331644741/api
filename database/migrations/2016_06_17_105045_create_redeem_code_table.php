<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRedeemCodeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('redeem_code', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('rel_id',false,true);
            $table->string('code');
            $table->tinyInteger('is_use',false,true);//是否可用0可用1已使用
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
        Schema::drop('redeem_code');
    }
}
