<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateNullableToUserAttributesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_attributes', function (Blueprint $table) {
            $table->integer('number')->nullable()->default(null)->change();
            $table->string('string')->nullable()->default(null)->change();
            $table->text('text')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_attributes', function (Blueprint $table) {

        });
    }
}
