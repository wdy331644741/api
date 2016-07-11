<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PutTimeToAppStartpagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('app_startpages', function (Blueprint $table) {
            $table->dateTime('online_time')->nullable()->default(NULL)->change();
            $table->dateTime('offline_time')->nullable()->default(NULL)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('app_startpages', function (Blueprint $table) {
            $table->timestamp('online_time');
            $table->timestamp('offline_time');
        });
    }
}
