<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateActivityJoinsAddIndex20160918011 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('activity_joins', function (Blueprint $table) {
            $table->index(['user_id', 'activity_id']);
            $table->index(['user_id', 'alias_name']);
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
            $table->dropIndex(['user_id', 'activity_id']);
            $table->dropIndex(['user_id', 'alias_name']);
        });
    }
}
