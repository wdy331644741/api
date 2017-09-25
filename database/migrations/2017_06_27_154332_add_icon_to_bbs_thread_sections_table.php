<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIconToBbsThreadSectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bbs_thread_sections', function (Blueprint $table) {
            $table->tinyInteger('pid')->default(0)->comment('主板快id');
            $table->string('icon')->comment('版块图标');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bbs_thread_sections', function (Blueprint $table) {
            $table->dropColumn(['pid','icon']);
        });
    }
}
