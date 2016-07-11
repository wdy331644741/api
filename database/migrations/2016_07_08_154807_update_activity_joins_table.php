<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateActivityJoinsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('activity_joins', function (Blueprint $table) {
            $table->string('alias_name');
            $table->boolean('shared')->default(0);
            $table->integer('continue');
            $table->dropColumn(['user_from', 'is_rereceive', 'rereceive_time']);
            $table->index('user_id');
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
            $table->dropIndex('activity_joins_user_id_index');
            $table->dropColumn(['alias_name', 'continue', 'shared']);
            $table->string('user_from');
            $table->tinyInteger('is_rereceive');
            $table->timestamp('rereceive_time');
        });
    }
}
