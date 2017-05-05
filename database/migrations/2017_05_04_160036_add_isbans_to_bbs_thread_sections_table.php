<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsbansToBbsThreadSectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bbs_thread_sections', function (Blueprint $table) {
            $table->tinyInteger('isban')->default(0)->comment('版块是否禁用普通用户发帖');
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
            $table->dropColumn('isban');
        });
    }
}
