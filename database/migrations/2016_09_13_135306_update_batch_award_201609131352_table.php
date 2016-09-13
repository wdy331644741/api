<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateBatchAward201609131352Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('award_batch', function (Blueprint $table) {
            $table->integer('send_num')->nullable()->default(null);
        });
        //
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('award_batch', function (Blueprint $table) {
            $table->dropColumn('send_num');
        });
        //
    }
}
