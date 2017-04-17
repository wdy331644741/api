<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateMoneyShare2017021001 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('money_share', function (Blueprint $table) {
            $table->integer('record_id',false,true)->nullable()->default(0)->after('user_name')->comment("投资记录id");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('money_share', function (Blueprint $table) {
            $table->dropColumn('record_id');
        });
    }
}
