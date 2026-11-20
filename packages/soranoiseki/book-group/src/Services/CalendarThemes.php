<?php

namespace Soranoiseki\BookGroup\Services;

/**
 * Themes of the calendar PDF: a template, a stylesheet and a settings schema, kept in
 * storage/scripts/calendar/themes/<folder>/ together with a theme.json ({key, name, description, template, css, settings}).
 * Themes are written by the developer and committed to git; users only pick one when they create a design (it cannot be
 * changed afterwards). The key comes from theme.json, not from the folder name.
 *
 * calendar_pdf.py reads the same theme.json files to render, so a key accepted here is always found there.
 */
class CalendarThemes
{
    /** The first theme; designs fall back to it and all calendars made before themes existed use it. */
    public const DEFAULT = 'classic';

    private function root(): string
    {
        return storage_path('scripts/calendar/themes');
    }

    /** @return array<string, array{dir: string, info: array}> */
    private function manifests(): array
    {
        $found = [];
        foreach (glob($this->root() . '/*/theme.json') ?: [] as $manifest) {
            $info = json_decode((string) file_get_contents($manifest), true);
            $key = is_array($info) ? ($info['key'] ?? null) : null;
            if (!is_string($key) || !preg_match('/^[a-z0-9][a-z0-9_-]{0,49}$/', $key) || isset($found[$key])) {
                continue;
            }
            $found[$key] = ['dir' => dirname($manifest), 'info' => $info];
        }
        ksort($found);

        return $found;
    }

    /** @return array<string, array{key: string, name: string, description: string}> themes keyed by their key */
    public function all(): array
    {
        $themes = [];
        foreach ($this->manifests() as $key => $theme) {
            $themes[$key] = [
                'key' => $key,
                'name' => (string) ($theme['info']['name'] ?? $key),
                'description' => (string) ($theme['info']['description'] ?? ''),
            ];
        }

        return $themes;
    }

    /** The default theme first, then the others in alphabetical order of their keys. */
    public function list(): array
    {
        $themes = $this->all();
        $default = $themes[self::DEFAULT] ?? null;
        unset($themes[self::DEFAULT]);

        return array_values(array_filter([$default, ...array_values($themes)]));
    }

    public function keys(): array
    {
        return array_keys($this->all());
    }

    public function exists(?string $key): bool
    {
        return $key !== null && isset($this->all()[$key]);
    }

    /** Display name of a theme; the key itself for one that no longer exists. */
    public function name(?string $key): string
    {
        return $this->all()[$key ?? self::DEFAULT]['name'] ?? (string) $key;
    }

    /**
     * The settings schema file of a theme (an unknown key gets the default theme's). It may live in another theme's
     * folder, but must stay inside themes/.
     */
    public function schemaPath(?string $key): string
    {
        $manifests = $this->manifests();
        $theme = $manifests[$key ?? ''] ?? $manifests[self::DEFAULT] ?? null;
        if (!$theme) {
            throw new \RuntimeException('The default calendar theme is missing.');
        }

        $path = realpath($theme['dir'] . '/' . ($theme['info']['settings'] ?? 'settings_schema.json'));
        $root = realpath($this->root());
        if ($path === false || !str_starts_with($path, $root . DIRECTORY_SEPARATOR)) {
            throw new \RuntimeException('Invalid settings schema in the calendar theme ' . $key);
        }

        return $path;
    }
}
