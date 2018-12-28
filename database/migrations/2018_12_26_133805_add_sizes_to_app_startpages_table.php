<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSizesToAppStartpagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('app_startpages', function (Blueprint $table) {
            $table->string('img6')->nullable()->default(NULL)->comment('ios:1242x2688 android:无');
            $table->string('img7')->nullable()->default(NULL)->comment('	ios:828x1792 android:无');
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
            $table->dropColumn(['img6','img7']);
        });
    }
}
