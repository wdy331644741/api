<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateActivityVoteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('activity_vote', function (Blueprint $table) {
            $table->integer('rank_add')->comment("第一次投票时的名次 这里加上了日活量");;
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('activity_vote', function (Blueprint $table) {
            $table->dropColumn(["rank_add"]);
        });
    }
}
