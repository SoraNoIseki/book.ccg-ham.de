<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('song_content', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('file')->nullable(false);
            $table->integer('file_index')->nullable(false)->default(0);
            $table->string('name')->nullable(false);
            $table->string('band')->nullable(true);
            $table->string('album')->nullable(true);
            $table->text('text')->nullable(true);
            $table->string('link_id')->nullable(true);
            $table->boolean('hidden')->default(false);
            $table->boolean('archived')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('song_content');
    }
};
