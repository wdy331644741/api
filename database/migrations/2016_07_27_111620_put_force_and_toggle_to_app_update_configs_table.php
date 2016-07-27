<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PutForceAndToggleToAppUpdateConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('app_update_configs', function (Blueprint $table) {
            $table->integer('force')->change();
            $table->integer('toggle')->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('app_update_configs', function (Blueprint $table) {
            $table->string('force');
            $table->string('toggle')->default('off');
        });
    }
}
