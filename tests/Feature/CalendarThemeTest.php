<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Soranoiseki\BookGroup\Models\Calendar\Design;
use Soranoiseki\BookGroup\Services\CalendarThemes;
use Tests\TestCase;

/** A design uses one theme (template, stylesheet, settings schema) chosen when it is created; themes are files. */
class CalendarThemeTest extends TestCase
{
    use RefreshDatabase;

    private string $extraTheme;

    protected function setUp(): void
    {
        parent::setUp();

        // a second theme next to "classic"; the folder name differs from the key on purpose
        $this->extraTheme = storage_path('scripts/calendar/themes/zz-test-folder');
        mkdir($this->extraTheme, 0775, true);
        file_put_contents($this->extraTheme . '/settings_schema.json', json_encode([
            'groups' => [['id' => 'colors', 'label' => 'Farben']],
            'settings' => [['key' => 'accent2', 'group' => 'colors', 'label' => 'Akzent', 'type' => 'color', 'default' => '#112233', 'css' => true]],
        ]));
        file_put_contents($this->extraTheme . '/theme.json', json_encode(['key' => 'zz-test', 'name' => 'Test', 'template' => '../classic/template.html.j2', 'css' => '../classic/calendar.css', 'settings' => 'settings_schema.json']));
    }

    protected function tearDown(): void
    {
        @unlink($this->extraTheme . '/theme.json');
        @unlink($this->extraTheme . '/settings_schema.json');
        @rmdir($this->extraTheme);

        parent::tearDown();
    }

    private function user(string ...$roles): User
    {
        $user = User::factory()->create(['firstname' => 'Test', 'lastname' => 'User', 'enabled' => true]);
        foreach ($roles as $name) {
            $user->roles()->attach(Role::firstOrCreate(['internal_name' => $name], ['name' => $name])->id);
        }

        return $user;
    }

    public function test_themes_are_found_by_their_key(): void
    {
        $themes = app(CalendarThemes::class);

        $keys = array_column($themes->list(), 'key');
        $this->assertSame('classic', $keys[0]); // the default theme comes first
        $this->assertContains('modern', $keys);
        $this->assertContains('zz-test', $keys);
        $this->assertTrue($themes->exists('zz-test'));
        $this->assertFalse($themes->exists('zz-test-folder'));
        $this->assertFalse($themes->exists('../classic'));
    }

    public function test_every_theme_has_a_usable_schema_and_files(): void
    {
        $themes = app(CalendarThemes::class);
        $settings = app(\Soranoiseki\BookGroup\Services\CalendarSettings::class);

        foreach (array_diff($themes->keys(), ['zz-test']) as $key) {
            $dir = storage_path('scripts/calendar/themes/' . $key);
            $manifest = json_decode(file_get_contents($dir . '/theme.json'), true);
            foreach (['template', 'css', 'settings'] as $file) {
                $this->assertFileExists($dir . '/' . $manifest[$file], "$key: $file");
            }

            // the defaults of a schema must pass its own validation
            $defaults = $settings->defaults($key);
            $this->assertNotEmpty($defaults, $key);
            $this->assertSame($defaults, $settings->resolve($settings->validate($defaults, $key), $key), $key);
        }
    }

    public function test_existing_designs_use_the_classic_theme(): void
    {
        $this->assertSame('classic', Design::orderBy('id')->first()->theme);
    }

    private function designer(): User
    {
        return $this->user('calendar', 'calendar_design');
    }

    public function test_a_new_design_gets_the_defaults_and_schema_of_its_theme(): void
    {
        $response = $this->actingAs($this->designer())->postJson('/api/calendar/designs', ['name' => 'Neu', 'theme' => 'zz-test'])
            ->assertOk()
            ->assertJsonPath('design.theme', 'zz-test')
            ->assertJsonPath('design.theme_name', 'Test')
            ->assertJsonPath('design.settings', ['accent2' => '#112233'])
            ->assertJsonPath('design.settingsSchema.settings.0.key', 'accent2');

        $this->assertSame('zz-test', Design::find($response->json('design.id'))->theme);
    }

    public function test_settings_are_validated_against_the_schema_of_the_designs_theme(): void
    {
        $design = Design::create(['name' => 'Neu', 'theme' => 'zz-test', 'settings' => ['accent2' => '#112233'], 'styles' => '']);
        $designer = $this->designer();

        // "accent" belongs to classic, not to this theme
        $this->actingAs($designer)->putJson("/api/calendar/designs/{$design->id}/content", ['settings' => ['accent' => '#899211'], 'styles' => ''])
            ->assertStatus(422);

        $this->actingAs($designer)->putJson("/api/calendar/designs/{$design->id}/content", ['settings' => ['accent2' => '#abcdef'], 'styles' => ''])
            ->assertOk()->assertJsonPath('design.settings.accent2', '#abcdef');
        $this->assertSame('zz-test', $design->refresh()->theme);
    }

    public function test_the_theme_cannot_be_changed_afterwards(): void
    {
        $design = Design::orderBy('id')->first();

        $this->actingAs($this->designer())->putJson("/api/calendar/designs/{$design->id}/content", ['settings' => ['accent' => '#899211'], 'styles' => 'a { color: red; }', 'theme' => 'zz-test'])
            ->assertOk()->assertJsonPath('design.theme', 'classic');
        $this->assertSame('classic', $design->refresh()->theme);
    }

    public function test_restore_works_with_the_schema_of_the_theme(): void
    {
        $design = Design::create(['name' => 'Neu', 'theme' => 'zz-test', 'settings' => ['accent2' => '#112233'], 'styles' => '']);
        $designer = $this->designer();

        $this->actingAs($designer)->putJson("/api/calendar/designs/{$design->id}/content", ['settings' => ['accent2' => '#aaaaaa'], 'styles' => ''])->assertOk();
        $this->actingAs($designer)->putJson("/api/calendar/designs/{$design->id}/content", ['settings' => ['accent2' => '#bbbbbb'], 'styles' => ''])->assertOk();
        $first = $design->revisions()->orderBy('id')->first();
        $this->assertSame('zz-test', $first->theme);

        $this->actingAs($designer)->postJson("/api/calendar/designs/{$design->id}/restore/{$first->id}")
            ->assertOk()->assertJsonPath('design.settings.accent2', '#aaaaaa');
    }

    public function test_unknown_theme_or_no_source_is_rejected(): void
    {
        $designer = $this->designer();

        foreach ([['theme' => 'nope'], ['theme' => '../classic'], ['theme' => 'zz-test-folder'], []] as $input) {
            $this->actingAs($designer)->postJson('/api/calendar/designs', ['name' => 'Neu'] + $input)->assertStatus(422);
        }
        $this->assertSame(1, Design::count());
    }

    public function test_a_copy_keeps_the_theme_and_cannot_pick_another(): void
    {
        $design = Design::create(['name' => 'Quelle', 'theme' => 'zz-test', 'settings' => ['accent2' => '#abcdef'], 'styles' => '']);
        $designer = $this->designer();

        $this->actingAs($designer)->postJson('/api/calendar/designs', ['name' => 'Kopie', 'copy_from' => $design->id])
            ->assertOk()->assertJsonPath('design.theme', 'zz-test')->assertJsonPath('design.settings.accent2', '#abcdef');

        $this->actingAs($designer)->postJson('/api/calendar/designs', ['name' => 'Kopie 2', 'copy_from' => $design->id, 'theme' => 'classic'])
            ->assertStatus(422);
    }

    public function test_only_designers_can_create_designs(): void
    {
        $this->actingAs($this->user('calendar'))->postJson('/api/calendar/designs', ['name' => 'Neu', 'theme' => 'zz-test'])->assertForbidden();
    }
}
