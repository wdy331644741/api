<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCmsContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cms_contents', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('type_id');
            $table->string('cover')->comment('当type_id为1时，封面图片不能为空');
            $table->string('title');
            $table->text('contents');
            $table->tinyInteger('release')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('cms_contents');
    }
}
