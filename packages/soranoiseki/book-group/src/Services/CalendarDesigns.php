<?php

namespace Soranoiseki\BookGroup\Services;

use Illuminate\Support\Facades\DB;
use Soranoiseki\BookGroup\Models\Calendar\Calendar;
use Soranoiseki\BookGroup\Models\Calendar\Design;
use Soranoiseki\BookGroup\Models\Calendar\DesignRevision;

/**
 * Appearance designs: listing, saving with a change record, and the records themselves.
 *
 * Every change is one row in calendar_design_revision (who, when, the full state afterwards). The page lists
 * only the latest HISTORY_SHOWN, but all of them are kept.
 */
class CalendarDesigns
{
    public const HISTORY_SHOWN = 10;

    /** Role that may create, edit, rename and delete designs (calendar makers can only choose one). */
    public const DESIGN_ROLE = 'calendar_design';

    public function __construct(private CalendarSettings $settings)
    {
    }

    public function isDesigner($user): bool
    {
        return $user && $user->hasRole(self::DESIGN_ROLE);
    }

    /** All designs with the years of the calendars that use them. */
    public function list(): array
    {
        $years = Calendar::whereNotNull('design_id')->orderBy('year')->get(['design_id', 'year'])
            ->groupBy('design_id')->map(fn ($rows) => $rows->pluck('year')->map(fn ($y) => (int) $y)->values()->all());

        // The default design is the first one ever created (it is also what new calendars fall back to);
        // all others follow, newest first.
        $designs = Design::orderBy('id')->get(['id', 'name', 'created_at']);
        $default = $designs->shift();
        $rest = $designs->sortByDesc(fn (Design $d) => [$d->created_at?->getTimestamp() ?? 0, $d->id]);

        return collect([$default])->filter()->concat($rest)->map(fn (Design $d) => [
            'id' => $d->id,
            'name' => $d->name,
            'used_by' => $years[$d->id] ?? [],
        ])->values()->all();
    }

    /** One design with its latest change records. */
    public function detail(Design $design): array
    {
        return [
            'id' => $design->id,
            'name' => $design->name,
            'settings' => $this->settings->forDesign($design),
            'styles' => (string) $design->styles,
            'history' => $this->history($design),
            'historyTotal' => $design->revisions()->count(),
        ];
    }

    public function same(Design $design, array $settings, string $styles): bool
    {
        return $this->settings->forDesign($design) == $settings && (string) $design->styles === $styles;
    }

    /**
     * Store new content on a design and write the change to its records, in one transaction.
     * The first record of a design that already has custom content also keeps the state before it ("initial"),
     * so that there is always something to go back to.
     */
    public function apply(Design $design, array $settings, string $styles, $user, string $action, ?int $restoredFromId = null): void
    {
        DB::transaction(function () use ($design, $settings, $styles, $user, $action, $restoredFromId) {
            $current = $this->settings->forDesign($design);
            $customised = $current != $this->settings->defaults() || (string) $design->styles !== '';

            if ($customised && !$design->revisions()->exists()) {
                $initial = $design->revisions()->create([
                    'action' => 'initial',
                    'settings' => $current,
                    'styles' => (string) $design->styles,
                ]);
                // created_at defaults to now, but the state itself is older
                DesignRevision::where('id', $initial->id)->update(['created_at' => $design->updated_at ?? now()]);
            }

            $design->settings = $settings;
            $design->styles = $styles;
            $design->save();

            $this->record($design, $user, $action, $settings, $styles, $restoredFromId);
        });
    }

    public function record(Design $design, $user, string $action, array $settings, string $styles, ?int $restoredFromId = null): DesignRevision
    {
        return $design->revisions()->create([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'action' => $action,
            'restored_from_id' => $restoredFromId,
            'settings' => $settings,
            'styles' => $styles,
        ]);
    }

    /** Latest records for the UI, without the (possibly large) CSS; differences are computed against the current design. */
    public function history(Design $design): array
    {
        $current = $this->settings->forDesign($design);

        return $design->revisions()->with('restoredFrom:id,created_at,user_name')
            ->orderByDesc('id')->limit(self::HISTORY_SHOWN)->get()
            ->map(function (DesignRevision $r) use ($current, $design) {
                $settings = $this->settings->resolve($r->settings);

                return [
                    'id' => $r->id,
                    'created_at' => $r->created_at->toIso8601String(),
                    'user_name' => $r->user_name,
                    'action' => $r->action,
                    'restored_from' => $r->restoredFrom ? [
                        'id' => $r->restoredFrom->id,
                        'created_at' => $r->restoredFrom->created_at->toIso8601String(),
                        'user_name' => $r->restoredFrom->user_name,
                    ] : null,
                    'settings_changed' => count(array_filter($settings, fn ($v, $k) => ($current[$k] ?? null) !== $v, ARRAY_FILTER_USE_BOTH)),
                    'styles_changed' => (string) $r->styles !== (string) $design->styles,
                ];
            })->values()->all();
    }
}
