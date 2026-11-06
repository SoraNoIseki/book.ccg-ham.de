<?php

namespace Soranoiseki\BookGroup\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Soranoiseki\BookGroup\Models\Calendar\Design;
use Soranoiseki\BookGroup\Models\Calendar\DesignRevision;
use Soranoiseki\BookGroup\Services\CalendarDesigns;
use Soranoiseki\BookGroup\Services\CalendarSettings;
use Soranoiseki\BookGroup\Services\CalendarStyles;
use Soranoiseki\Core\Controllers\Controller;

/**
 * JSON API for the appearance designs. Reading is open to everyone who can use the calendar page;
 * creating, editing, renaming, deleting and restoring needs the "calendar_design" role.
 */
class CalendarDesignController extends Controller
{
    public function __construct(
        private CalendarDesigns $designs,
        private CalendarSettings $settings,
        private CalendarStyles $styles,
    ) {
    }

    /** GET /api/calendar/designs */
    public function index(): JsonResponse
    {
        return response()->json(['success' => true, 'designs' => $this->designs->list()]);
    }

    /** GET /api/calendar/designs/{design} */
    public function show(Design $design): JsonResponse
    {
        return response()->json($this->payload($design));
    }

    /** POST /api/calendar/designs  { name, copy_from? } */
    public function store(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessDesigner($request)) {
            return $denied;
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', $this->uniqueName()],
            'copy_from' => ['nullable', 'integer', Rule::exists('calendar_design', 'id')->whereNull('deleted_at')],
        ], $this->nameMessages());

        $source = !empty($data['copy_from']) ? Design::find($data['copy_from']) : null;
        $settings = $source ? $this->settings->forDesign($source) : $this->settings->defaults();
        $styles = $source ? (string) $source->styles : '';

        $design = DB::transaction(function () use ($data, $request, $settings, $styles) {
            $design = Design::create([
                'name' => trim($data['name']),
                'created_by' => $request->user()?->id,
                'settings' => $settings,
                'styles' => $styles,
            ]);
            $this->designs->record($design, $request->user(), 'create', $settings, $styles);

            return $design;
        });

        return response()->json($this->payload($design) + ['message' => '已创建外观「' . $design->name . '」。']);
    }

    /** PATCH /api/calendar/designs/{design}  { name } */
    public function rename(Request $request, Design $design): JsonResponse
    {
        if ($denied = $this->denyUnlessDesigner($request)) {
            return $denied;
        }

        $data = $request->validate(['name' => ['required', 'string', 'max:100', $this->uniqueName($design)]], $this->nameMessages());
        $design->update(['name' => trim($data['name'])]);

        return response()->json($this->payload($design) + ['message' => '已重命名。']);
    }

    /** PUT /api/calendar/designs/{design}/content  { settings, styles } */
    public function update(Request $request, Design $design): JsonResponse
    {
        if ($denied = $this->denyUnlessDesigner($request)) {
            return $denied;
        }

        $request->validate(['settings' => 'required|array', 'styles' => 'nullable|string']);

        $settings = array_replace($this->settings->forDesign($design), $this->settings->validate($request->input('settings')));
        $styles = $this->styles->validate($request->input('styles'));

        if ($this->designs->same($design, $settings, $styles)) {
            return response()->json($this->payload($design) + ['message' => '没有需要保存的修改。']);
        }

        $this->designs->apply($design, $settings, $styles, $request->user(), 'save');

        return response()->json($this->payload($design->refresh()) + ['message' => '外观已保存。']);
    }

    /** DELETE /api/calendar/designs/{design} */
    public function destroy(Request $request, Design $design): JsonResponse
    {
        if ($denied = $this->denyUnlessDesigner($request)) {
            return $denied;
        }

        $years = $design->calendars()->orderBy('year')->pluck('year');
        if ($years->isNotEmpty()) {
            return $this->fail('「' . $design->name . '」正在被 ' . $years->implode('、') . ' 年的日历使用，请先让这些日历改用其他外观。', 409);
        }
        if (Design::count() <= 1) {
            return $this->fail('至少要保留一套外观。', 409);
        }

        $design->delete(); // soft delete: its change records stay

        return response()->json(['success' => true, 'designs' => $this->designs->list(), 'message' => '已删除外观「' . $design->name . '」。']);
    }

    /**
     * POST /api/calendar/designs/{design}/restore/{revision}
     * Applies an earlier state again. This is recorded as a change of its own that names the record it went back to.
     */
    public function restore(Request $request, Design $design, int $revision): JsonResponse
    {
        if ($denied = $this->denyUnlessDesigner($request)) {
            return $denied;
        }

        $target = DesignRevision::where('design_id', $design->id)->find($revision);
        if (!$target) {
            return $this->fail('找不到这条修改记录。', 404);
        }

        $settings = $this->settings->resolve($target->settings);
        $styles = $this->styles->validate((string) $target->styles); // the rules may have changed since it was saved

        if ($this->designs->same($design, $settings, $styles)) {
            return response()->json($this->payload($design) + ['message' => '该记录与当前外观相同，无需恢复。']);
        }

        $this->designs->apply($design, $settings, $styles, $request->user(), 'restore', $target->id);

        return response()->json($this->payload($design->refresh()) + ['message' => '已恢复到所选记录的外观，并记录为一次修改。']);
    }

    // ----------------------------

    private function payload(Design $design): array
    {
        return [
            'success' => true,
            'design' => $this->designs->detail($design),
            'designs' => $this->designs->list(),
        ];
    }

    private function denyUnlessDesigner(Request $request): ?JsonResponse
    {
        return $this->designs->isDesigner($request->user()) ? null : $this->fail('你没有设计外观的权限。', 403);
    }

    private function fail(string $message, int $status): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }

    private function uniqueName(?Design $ignore = null)
    {
        $rule = Rule::unique('calendar_design', 'name')->whereNull('deleted_at');

        return $ignore ? $rule->ignore($ignore->id) : $rule;
    }

    private function nameMessages(): array
    {
        return [
            'name.required' => '请填写外观名称。',
            'name.max' => '外观名称不能超过 100 个字符。',
            'name.unique' => '已经有同名的外观了。',
            'copy_from.exists' => '要复制的外观不存在。',
        ];
    }
}
