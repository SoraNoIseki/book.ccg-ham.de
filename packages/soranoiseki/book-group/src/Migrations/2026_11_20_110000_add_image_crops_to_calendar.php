<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which part of a monthly photo shows in the header: per month slot ("01".."12") the centre (x, y as fractions of
 * the image) and a zoom factor, as JSON. Months without an entry are centred (the original behaviour). The crop is
 * computed from these numbers and the header ratio of the design when the PDF is made, so it survives a change of ratio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar', function (Blueprint $table) {
            $table->text('image_crops')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('calendar', function (Blueprint $table) {
            $table->dropColumn('image_crops');
        });
    }
};
