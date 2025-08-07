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
        Schema::create('dropbox_files', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->date('date')->nullable(false);
            $table->string('file_name')->nullable(true);
            $table->string('file_path')->nullable(true);
            $table->string('share_link')->nullable(true);
            $table->string('type')->nullable(true); // 'worship', 'recording', 'bulletin' etc.
            $table->json('raw')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dropbox_files');
    }
};
