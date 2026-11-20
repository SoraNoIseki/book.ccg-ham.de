<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Soranoiseki\BookGroup\Models\Calendar\Calendar;
use Soranoiseki\BookGroup\Models\Calendar\Detail;
use Soranoiseki\BookGroup\Models\Calendar\Event;
use Tests\TestCase;

/** Editing single imported rows (events and monthly verses) of a calendar. */
class CalendarEditTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        $user = User::factory()->create(['firstname' => 'Test', 'lastname' => 'User', 'enabled' => true]);
        $role = Role::firstOrCreate(['internal_name' => 'calendar'], ['name' => 'calendar']);
        $user->roles()->attach($role->id);

        return $user;
    }

    private function calendar(?int $year = null): Calendar
    {
        $calendar = new Calendar();
        $calendar->year = $year ?? (int) date('Y') + 1;
        $calendar->save();

        return $calendar;
    }

    private function event(Calendar $c, string $date, string $name = 'Alt'): Event
    {
        return Event::create(['year' => $c->year, 'date' => $date, 'name' => $name, 'type' => 'yearly', 'calendar_id' => $c->id]);
    }

    public function test_event_can_be_edited_and_lines_are_stored_with_pipes(): void
    {
        $c = $this->calendar();
        $e = $this->event($c, "{$c->year}-01-18");

        $this->actingAs($this->user())
            ->putJson("/api/calendar/{$c->id}/events/{$e->id}", ['date' => "{$c->year}-01-19", 'name' => "会友大会\n\n登录事选举\r\n"])
            ->assertOk()
            ->assertJsonPath('events.0.date', "{$c->year}-01-19")
            ->assertJsonPath('events.0.name', '会友大会|登录事选举');
    }

    public function test_event_date_must_be_in_the_year_and_free(): void
    {
        $c = $this->calendar();
        $e = $this->event($c, "{$c->year}-01-18");
        $this->event($c, "{$c->year}-02-01", 'Andere');
        $user = $this->user();

        $this->actingAs($user)->putJson("/api/calendar/{$c->id}/events/{$e->id}", ['date' => ($c->year + 2) . '-01-01', 'name' => 'x'])
            ->assertStatus(422);
        $this->actingAs($user)->putJson("/api/calendar/{$c->id}/events/{$e->id}", ['date' => "{$c->year}-02-01", 'name' => 'x'])
            ->assertStatus(422);
        $this->actingAs($user)->putJson("/api/calendar/{$c->id}/events/{$e->id}", ['date' => "{$c->year}-01-18", 'name' => " \n "])
            ->assertStatus(422);
        // Only days that show up in the calendar: the padding days of the January / December weeks
        $min = \Carbon\Carbon::create($c->year, 1, 1)->startOfWeek();
        $max = \Carbon\Carbon::create($c->year, 12, 31)->endOfWeek();
        foreach ([$min->copy()->subDay(), $max->copy()->addDay(), \Carbon\Carbon::create($c->year - 1, 12, 12)] as $outside) {
            $this->actingAs($user)->putJson("/api/calendar/{$c->id}/events/{$e->id}", ['date' => $outside->format('Y-m-d'), 'name' => 'x'])
                ->assertStatus(422);
        }
        foreach ([$min, $max] as $inside) {
            $this->actingAs($user)->putJson("/api/calendar/{$c->id}/events/{$e->id}", ['date' => $inside->format('Y-m-d'), 'name' => 'x'])
                ->assertOk();
        }
    }

    public function test_event_of_another_calendar_is_not_found(): void
    {
        $c = $this->calendar();
        $other = $this->calendar($c->year + 1);
        $e = $this->event($other, "{$other->year}-03-01");

        $this->actingAs($this->user())->putJson("/api/calendar/{$c->id}/events/{$e->id}", ['date' => "{$c->year}-03-01", 'name' => 'x'])
            ->assertNotFound();
    }

    public function test_bible_text_is_updated_or_created(): void
    {
        $c = $this->calendar();
        $user = $this->user();

        // Month without a verse yet -> created
        $this->actingAs($user)->putJson("/api/calendar/{$c->id}/bible-texts/3", ['text' => "Zeile 1\nZeile 2", 'source' => ' Joh 3,16 '])
            ->assertOk()->assertJsonPath('details.0.bible_text', 'Zeile 1|Zeile 2')->assertJsonPath('details.0.bible_source', 'Joh 3,16');

        // Existing month -> updated, no duplicate
        $this->actingAs($user)->putJson("/api/calendar/{$c->id}/bible-texts/3", ['text' => 'Neu', 'source' => ''])->assertOk();
        $this->assertSame(1, Detail::where('calendar_id', $c->id)->count());
        $this->assertSame('Neu', Detail::where('calendar_id', $c->id)->first()->bible_text);

        $this->actingAs($user)->putJson("/api/calendar/{$c->id}/bible-texts/13", ['text' => 'x'])->assertNotFound();
        $this->actingAs($user)->putJson("/api/calendar/{$c->id}/bible-texts/4", ['text' => '  '])->assertStatus(422);
    }

    public function test_bible_texts_can_be_rearranged(): void
    {
        $c = $this->calendar();
        $user = $this->user();
        foreach ([1 => 'Jan', 3 => 'Mar', 12 => 'Dez'] as $m => $t) {
            Detail::create(['calendar_id' => $c->id, 'year' => $c->year, 'month' => $m, 'bible_text' => $t, 'bible_source' => "S$m"]);
        }
        $texts = fn () => Detail::where('calendar_id', $c->id)->orderBy('month')->pluck('bible_text', 'month')->all();

        // Swap January and March
        $this->actingAs($user)->putJson("/api/calendar/{$c->id}/bible-texts/order", ['order' => [3, 2, 1, 4, 5, 6, 7, 8, 9, 10, 11, 12]])
            ->assertOk();
        $this->assertSame([1 => 'Mar', 3 => 'Jan', 12 => 'Dez'], $texts());

        // Move December to after September (10, 11, 12 shift down); the unset months move along
        $this->actingAs($user)->putJson("/api/calendar/{$c->id}/bible-texts/order", ['order' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 12, 10, 11]])
            ->assertOk();
        $this->assertSame([1 => 'Mar', 3 => 'Jan', 10 => 'Dez'], $texts());
        $this->assertSame('S12', Detail::where('calendar_id', $c->id)->where('month', 10)->value('bible_source'));

        // Not a permutation of 1..12
        foreach ([[1, 1, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12], [1, 2, 3], [0, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 13]] as $bad) {
            $this->actingAs($user)->putJson("/api/calendar/{$c->id}/bible-texts/order", ['order' => $bad])->assertStatus(422);
        }
        $this->assertSame([1 => 'Mar', 3 => 'Jan', 10 => 'Dez'], $texts());
    }

    public function test_history_calendars_and_users_without_the_role_cannot_edit(): void
    {
        $old = $this->calendar((int) date('Y') - 1);
        $e = $this->event($old, "{$old->year}-01-18");

        $this->actingAs($this->user())->putJson("/api/calendar/{$old->id}/events/{$e->id}", ['date' => "{$old->year}-01-19", 'name' => 'x'])
            ->assertForbidden();
        $this->actingAs($this->user())->putJson("/api/calendar/{$old->id}/bible-texts/1", ['text' => 'x'])->assertForbidden();
        $this->actingAs($this->user())->putJson("/api/calendar/{$old->id}/bible-texts/order", ['order' => range(1, 12)])->assertForbidden();

        $c = $this->calendar();
        $nobody = User::factory()->create(['firstname' => 'No', 'lastname' => 'Body', 'enabled' => true]);
        $this->actingAs($nobody)->putJson("/api/calendar/{$c->id}/bible-texts/1", ['text' => 'x'])->assertUnauthorized();
    }
}
