<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTableChannels20180307 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('channels', function (Blueprint $table) {
            $table->tinyInteger('is_disable')->default(0)->comment("是否禁用 0启用，1禁用");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn(["is_disable"]);
        });
    }
}
