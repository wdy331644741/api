<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateActivityJoins201607140959Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('activity_joins', function (Blueprint $table) {
            $table->integer('award_type')->nullable()->default(null);
            $table->integer('award_id')->nullable()->default(null);
            $table->integer('award_name')->nullable()->default(null);
            $table->tinyInteger('isExternal')->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('activity_joins', function (Blueprint $table) {
            $table->dropColumn(['award_type', 'award_id', 'award_name', 'isExternal']);
        });
    }
}
