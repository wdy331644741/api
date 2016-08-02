<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateBanners201608010253Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->renameColumn('img_url', 'url');
            $table->tinyInteger('type')->nullable()->default(null);
            $table->string('short_desc', 256)->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->renameColumn('url', 'img_url');
            $table->dropColumn('type');
            $table->dropColumn('short_desc');
        });       
    }
}
