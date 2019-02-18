<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateshuliTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shuli', function (Blueprint $table) {
            $table->increments('id');
            $table->text('description');
            $table->string('ji_xiong');
            $table->text('ji_ye');
            $table->text('jia_ting');
            $table->text('jian_kang');
            $table->text('han_yi');
            $table->text('qian_tu');
            $table->text('cai_yun');
            $table->text('all_description');
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
        Schema::dropIfExists('shuli');
    }
}
