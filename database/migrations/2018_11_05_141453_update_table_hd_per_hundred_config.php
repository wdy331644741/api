<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTableHdPerHundredConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hd_per_hundred_config', function (Blueprint $table) {
            $table->decimal('ultimate_price')->unsigned()->default('0.00')->comment('终极大奖价值');
            $table->decimal('first_price')->unsigned()->default('0.00')->comment('一马当先奖价值');
            $table->decimal('last_price')->unsigned()->default('0.00')->comment('一锤定音价值');
            $table->decimal('sunshine_price')->unsigned()->default('0.00')->comment('阳光普照奖价值');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hd_per_hundred_config', function (Blueprint $table) {
            //
            $table->dropColumn(["ultimate_price", "first_price","last_price","sunshine_price"]);
        });
    }
}
