<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateBannersAddTag201704171109 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->string('tag')->nullable()->default(null);
            $table->index('position');
            $table->index('tag');
            $table->index(['position','tag'],'position_tag_index');
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
            $table->dropIndex('banners_tag_index');
            $table->dropIndex('banners_position_index');
            $table->dropIndex('position_tag_index');
            $table->dropColumn('tag');
        });
    }
}
