<?php

namespace Soranoiseki\BookGroup\Services;

use Illuminate\Validation\ValidationException;

/**
 * Validates the custom CSS of a calendar (calendar.styles) before it is stored or rendered.
 *
 * The stylesheet is user input that WeasyPrint will interpret, and WeasyPrint resolves url() itself.
 * Two layers protect the server: this check rejects the obvious problems with a readable message and a
 * line number, and the PDF script only lets WeasyPrint read local files inside the calendar's image folder
 * and its own folder (RestrictedFetcher), so anything that slips through still cannot reach other files
 * or the network.
 */
class CalendarStyles
{
    public const MAX_BYTES = 20480;

    /** @var array<string, string> regex => message */
    private const FORBIDDEN = [
        '/@import\b/i' => '不支持 @import。',
        '/</' => '不能包含 < 字符。',
        '#\b(?:https?|ftp|file|javascript):#i' => '不允许使用网址或 file: 地址，图片请直接写文件名，例如 url(01.jpg)。',
        '#(?:^|[\s(\'"])//#' => '不允许使用以 // 开头的地址。',
        '#\.\.[/\\\\]#' => '路径中不能包含 ../。',
    ];

    /**
     * @throws ValidationException
     */
    public function validate(?string $css): string
    {
        $css = str_replace(["\r\n", "\r"], "\n", (string) $css);
        $css = preg_replace('/^\xEF\xBB\xBF/', '', $css); // BOM
        $css = trim($css);

        if ($css === '') {
            return '';
        }

        if (!mb_check_encoding($css, 'UTF-8')) {
            $this->fail(['自定义 CSS 必须是 UTF-8 文本。']);
        }

        if (strlen($css) > self::MAX_BYTES) {
            $this->fail(['自定义 CSS 超过了 ' . (self::MAX_BYTES / 1024) . ' KB 的上限（当前 ' . round(strlen($css) / 1024, 1) . ' KB）。']);
        }

        $errors = [];
        foreach (explode("\n", $css) as $index => $line) {
            foreach (self::FORBIDDEN as $pattern => $message) {
                if (preg_match($pattern, $line)) {
                    $errors[] = '第 ' . ($index + 1) . ' 行：' . $message;
                }
            }
        }

        if ($errors) {
            $this->fail(array_values(array_unique($errors)));
        }

        return $css;
    }

    private function fail(array $messages): never
    {
        throw ValidationException::withMessages(['styles' => $messages]);
    }
}
