<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Appearance designs become a shared library: several named designs, each calendar uses one of them.
 * Until now every calendar carried its own settings/styles (and its own change records).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_design', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes(); // a deleted design keeps its change records readable
            $table->string('name', 100);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->longText('settings')->nullable(); // JSON
            $table->longText('styles')->nullable();
        });

        Schema::table('calendar', function (Blueprint $table) {
            $table->unsignedBigInteger('design_id')->nullable()->index();
        });

        Schema::table('calendar_design_revision', function (Blueprint $table) {
            $table->unsignedBigInteger('design_id')->nullable()->index();
        });

        // ---- move existing data ----
        $schema = json_decode(file_get_contents(storage_path('scripts/calendar/settings_schema.json')), true);
        $defaults = [];
        foreach ($schema['settings'] as $item) {
            $defaults[$item['key']] = $item['default'];
        }
        $now = now();

        $defaultId = DB::table('calendar_design')->insertGetId([
            'name' => '默认外观',
            'settings' => json_encode($defaults),
            'styles' => '',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach (DB::table('calendar')->orderBy('year')->get() as $calendar) {
            $settings = $calendar->settings ? (json_decode($calendar->settings, true) ?: []) : [];
            $styles = (string) $calendar->styles;
            $merged = array_replace($defaults, $settings);
            $hasRevisions = DB::table('calendar_design_revision')->where('calendar_id', $calendar->id)->exists();

            if ($merged != $defaults || $styles !== '' || $hasRevisions) {
                // This calendar had its own look: it keeps it as a design of its own, with its change records.
                $designId = DB::table('calendar_design')->insertGetId([
                    'name' => $calendar->year . ' 年外观',
                    'settings' => json_encode($merged),
                    'styles' => $styles,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                DB::table('calendar_design_revision')->where('calendar_id', $calendar->id)->update(['design_id' => $designId]);
            } else {
                $designId = $defaultId;
            }

            DB::table('calendar')->where('id', $calendar->id)->update(['design_id' => $designId]);
        }

        Schema::table('calendar_design_revision', function (Blueprint $table) {
            $table->dropColumn('calendar_id');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_design_revision', function (Blueprint $table) {
            $table->unsignedBigInteger('calendar_id')->nullable()->index();
            $table->dropColumn('design_id');
        });
        Schema::table('calendar', function (Blueprint $table) {
            $table->dropColumn('design_id');
        });
        Schema::dropIfExists('calendar_design');
    }
};
