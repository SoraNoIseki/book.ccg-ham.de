<?php

namespace Soranoiseki\BookGroup\Services;

use Illuminate\Validation\ValidationException;
use Soranoiseki\BookGroup\Models\Calendar\Calendar;
use Soranoiseki\BookGroup\Models\Calendar\Design;

/**
 * Design settings (colors, sizes, toggles) of an appearance design.
 *
 * The single source of truth is storage/scripts/calendar/settings_schema.json: the PDF script reads
 * its defaults, this class validates input against it, and the Vue "design" tab is generated from it.
 * Stored values live in calendar.settings (JSON). A calendar keeps its own copy, so changing a
 * default later never changes how an older year renders.
 */
class CalendarSettings
{
    public function schema(): array
    {
        static $schema = null;

        return $schema ??= json_decode(file_get_contents(storage_path('scripts/calendar/settings_schema.json')), true);
    }

    /** @return array<string, array> items keyed by setting key */
    private function items(): array
    {
        $items = [];
        foreach ($this->schema()['settings'] as $item) {
            $items[$item['key']] = $item;
        }

        return $items;
    }

    public function defaults(): array
    {
        return array_map(fn ($item) => $item['default'], $this->items());
    }

    /** Defaults overridden by the stored values; unknown or invalid stored keys fall back to defaults. */
    public function resolve(?array $stored): array
    {
        $result = $this->defaults();
        foreach ((array) $stored as $key => $value) {
            $item = $this->items()[$key] ?? null;
            if ($item && $this->isValid($item, $value)) {
                $result[$key] = $this->cast($item, $value);
            }
        }

        return $result;
    }

    public function forDesign(?Design $design): array
    {
        return $this->resolve($design && is_array($design->settings) ? $design->settings : null);
    }

    /** Resolved settings of the design a calendar uses. */
    public function forCalendar(Calendar $calendar): array
    {
        return $this->forDesign($calendar->effectiveDesign());
    }

    /**
     * Validate user input strictly: unknown keys and invalid values are rejected (never passed on to the PDF).
     *
     * @throws ValidationException
     */
    public function validate(array $input): array
    {
        $items = $this->items();
        $errors = [];
        $clean = [];

        foreach ($input as $key => $value) {
            $item = $items[$key] ?? null;
            if (!$item) {
                $errors["settings.$key"] = ["未知的设置项：$key"];
            } elseif (!$this->isValid($item, $value)) {
                $errors["settings.$key"] = [$item['label'] . ' 的值无效。'];
            } else {
                $clean[$key] = $this->cast($item, $value);
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return $clean;
    }

    private function isValid(array $item, mixed $value): bool
    {
        return match ($item['type']) {
            'color' => is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1,
            'number' => is_numeric($value) && $value >= $item['min'] && $value <= $item['max'],
            'select' => in_array($value, array_column($item['options'], 'value'), false),
            'toggle' => is_bool($value),
            default => false,
        };
    }

    private function cast(array $item, mixed $value): mixed
    {
        return match ($item['type']) {
            'color' => strtolower($value),
            'number' => $value + 0,
            'select' => $value + 0,
            default => $value,
        };
    }
}
