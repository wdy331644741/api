<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateAward3Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('award_3', function (Blueprint $table) {
            $table->dropColumn('experience_amount_type');//体验类型
            $table->dropColumn('experience_amount_multiple');//体验金额倍数
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('award_3', function (Blueprint $table) {
            $table->tinyInteger('experience_amount_type', false, true);//体验类型
            $table->tinyInteger('experience_amount_multiple', false, true);//体验金额倍数
        });
    }
}
