<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateMoneyShareInfoAddIndex2017022701 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('money_share_info', function (Blueprint $table) {
            $table->index('main_id');
            $table->index('money');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('money_share_info', function (Blueprint $table) {
            $table->dropIndex('money_share_info_main_id_index');
            $table->dropIndex('money_share_info_money_index');
        });
    }
}
