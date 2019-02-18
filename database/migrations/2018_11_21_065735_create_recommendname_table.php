<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRecommendnameTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recommendnames', function (Blueprint $table) {
            $table->increments('id');
            $table->string('value1',10)->comment('名字第一个字');
            $table->string('value2',10)->comment('名字第二个字');
            $table->tinyInteger('num')->default(1)->comment('1为单名，2为双名');
            $table->tinyInteger('type')->default(1)->comment('1为男孩，2为女孩');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('recommendnames');
    }
}
