<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCalendarTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('calendar', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->integer('year')->nullable(false)->default(0);
            $table->text('settings')->nullable(true);
            $table->text('template')->nullable(true);
            $table->text('styles')->nullable(true);
            $table->text('data')->nullable(true);
        });

        Schema::create('calendar_event', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer('year')->nullable(true);
            $table->date('date')->nullable(true);
            $table->string('type')->nullable(true);
            $table->string('name')->nullable(true);
            $table->smallInteger('calendar_id')->nullable(true);
        });

        Schema::create('calendar_detail', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer('year')->nullable(false)->default(0);
            $table->integer('month')->nullable(true);
            $table->longText('bible_text')->nullable(true);
            $table->string('bible_source')->nullable(true);
            $table->string('image')->nullable(true);
            $table->smallInteger('calendar_id')->nullable(true);
        });

        Schema::create('calendar_holiday', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer('year')->nullable(true);
            $table->date('date')->nullable(true);
            $table->string('name_de')->nullable(true);
            $table->string('name_cn')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('calendar');
        Schema::dropIfExists('calendar_event');
        Schema::dropIfExists('calendar_holiday');
        Schema::dropIfExists('calendar_detail');
    }
}
