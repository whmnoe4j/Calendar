<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSancaiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sancai', function (Blueprint $table) {
            $table->increments('id');
            $table->string('sancai')->nullable()->comment('三才');
            $table->string('number')->nullable()->comment('数字');
            $table->text('description')->nullable()->comment('描述');
            $table->string('ji_xiong')->nullable()->comment('吉凶');
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
        Schema::dropIfExists('sancai');
    }
}
