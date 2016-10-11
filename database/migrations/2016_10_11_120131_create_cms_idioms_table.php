<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCmsIdiomsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cms_idioms', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->comment('说明');
            $table->text('contents');
            $table->dateTime('start_at')->nullable()->default(NULL);
            $table->dateTime('end_at')->nullable()->default(NULL);
            $table->tinyInteger('priority');
            $table->timestamps();
        });
        Schema::table('cms_idioms', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('cms_idioms');
    }
}
