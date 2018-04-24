<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateHdCollectCardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hd_collect_card', function (Blueprint $table) {
            $table->timestamp('effective_start');
            $table->timestamp('effective_end');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hd_collect_card', function (Blueprint $table) {
            $table->dropColumn('effective_start');
            $table->dropColumn('effective_end');
        });
    }
}
