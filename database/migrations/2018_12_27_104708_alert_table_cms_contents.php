<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlertTableCmsContents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cms_contents', function (Blueprint $table) {
            $table->dateTime('display_at')->nullable()->default(NULL)->comment('显示时间');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cms_contents', function (Blueprint $table) {
            $table->dropColumn('display_at');
        });
    }
}
