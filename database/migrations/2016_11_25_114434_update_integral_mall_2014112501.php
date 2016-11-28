<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateIntegralMall2014112501 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('integral_mall', function (Blueprint $table) {
            $table->string('photo_min')->default('')->after('photo');//配图(小)
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('integral_mall', function (Blueprint $table) {
            $table->dropColumn('photo_min');//配图(小)
        });
    }
}
