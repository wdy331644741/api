<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateAmountShare20170503 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('amount_share', function (Blueprint $table) {
            $table->string('multiple')->default(NULL)->after('identify')->comment("现金分享倍数默认0.001");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('amount_share', function (Blueprint $table) {
            $table->dropColumn('multiple');
        });
    }
}
