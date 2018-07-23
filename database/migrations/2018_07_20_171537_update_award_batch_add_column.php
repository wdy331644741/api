<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateAwardBatchAddColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('award_batch', function (Blueprint $table) {
            $table->text('params_data')->default('')->comment('传递过来的参数');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('award_batch', function (Blueprint $table) {
            $table->dropColumn(["params_data"]);
        });
    }
}
