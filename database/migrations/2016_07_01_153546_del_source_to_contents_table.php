<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DelSourceToContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cms_contents', function (Blueprint $table) {
            $table->dropColumn('source');
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
            $table->string('source')->nullable()->default(NULL);
        });
    }
}
