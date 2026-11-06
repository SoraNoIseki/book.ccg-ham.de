<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Every change to a design (parameters + custom CSS) is one row here:
 * a save, a restore (restored_from_id says to which earlier row), or the state a calendar had
 * before its first recorded change ("initial").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_design_revision', function (Blueprint $table) {
            $table->id();
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('calendar_id')->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable(); // snapshot: stays readable if the user is renamed or removed
            $table->string('action', 16)->default('save'); // save | restore | initial
            $table->unsignedBigInteger('restored_from_id')->nullable();
            $table->longText('settings')->nullable(); // JSON
            $table->longText('styles')->nullable();
        });

        // Move the history that an earlier version of this feature kept inside calendar.data
        foreach (DB::table('calendar')->whereNotNull('data')->get(['id', 'data']) as $calendar) {
            $data = json_decode($calendar->data, true);
            if (!is_array($data) || empty($data['history'])) {
                continue;
            }

            foreach (array_reverse($data['history']) as $entry) { // oldest first
                $user = $entry['user'] ?? null;
                DB::table('calendar_design_revision')->insert([
                    'created_at' => date('Y-m-d H:i:s', strtotime($entry['saved_at'] ?? 'now')),
                    'calendar_id' => $calendar->id,
                    'user_id' => $user['id'] ?? null,
                    'user_name' => $user['name'] ?? null,
                    'action' => $user ? 'save' : 'initial',
                    'settings' => json_encode($entry['settings'] ?? []),
                    'styles' => $entry['styles'] ?? '',
                ]);
            }

            unset($data['history']);
            DB::table('calendar')->where('id', $calendar->id)->update(['data' => $data ? json_encode($data) : null]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_design_revision');
    }
};
