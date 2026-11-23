<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A design is parameters + custom CSS on top of a theme (template + stylesheet, see CalendarThemes).
 * Everything that exists so far was made with the original layout, which is the "classic" theme.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_design', function (Blueprint $table) {
            $table->string('theme', 50)->default('classic');
        });

        Schema::table('calendar_design_revision', function (Blueprint $table) {
            $table->string('theme', 50)->default('classic');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_design_revision', function (Blueprint $table) {
            $table->dropColumn('theme');
        });

        Schema::table('calendar_design', function (Blueprint $table) {
            $table->dropColumn('theme');
        });
    }
};
