<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLoanBookTable2016082601 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loan_book', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',32);//姓名
            $table->string('phone',11);//手机号
            $table->string('city',10);//所在城市
            $table->string('collateral',32);//抵押物
            $table->string('amount',32);//贷款数额
            $table->tinyInteger('is_read',false,true);//是否已读
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
        Schema::drop('loan_book');
    }
}
