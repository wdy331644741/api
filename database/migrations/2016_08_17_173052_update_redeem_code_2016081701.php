<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateRedeemCode2016081701 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('redeem_code', function (Blueprint $table) {
            $table->index('code');//添加索引
            $table->integer('user_id',false,true)->nullable()->default(0);//用户id
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('redeem_code', function (Blueprint $table) {
            $table->dropColumn('user_id');//用户id
        });
    }
}
