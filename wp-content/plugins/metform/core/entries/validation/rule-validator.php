<?php

namespace MetForm\Core\Entries\Validation;

defined('ABSPATH') || exit;

/**
 * Keeps server-side field validation rules aligned with the form builder.
 */
class Rule_Validator {

    /**
     * Count whitespace-separated words, including numbers and Unicode text.
     *
     * @param mixed $value Field value.
     * @return int
     */
    public static function count_words($value) {
        $value = trim((string) $value);
        $words = @preg_split('/\s+/u', $value, -1, PREG_SPLIT_NO_EMPTY);

        if (false === $words) {
            $words = preg_split('/\s+/', $value, -1, PREG_SPLIT_NO_EMPTY);
        }

        return count($words ?: []);
    }

    /**
     * Count UTF-16 code units, matching JavaScript String#length.
     *
     * @param mixed $value Field value.
     * @return int
     */
    public static function count_characters($value) {
        $result = preg_match_all('/./us', (string) $value, $characters);

        if (false === $result) {
            return strlen((string) $value);
        }

        $length = 0;
        foreach ($characters[0] as $character) {
            $length += (4 === strlen($character)) ? 2 : 1;
        }

        return $length;
    }

    /**
     * Resolve custom validation-message placeholders.
     *
     * @param string $message Message template.
     * @param mixed  $min     Minimum value.
     * @param mixed  $max     Maximum value.
     * @return string
     */
    public static function resolve_message($message, $min, $max) {
        return trim(str_replace(['{{min}}', '{{max}}'], [(string) $min, (string) $max], (string) $message));
    }

    /**
     * Parse a numeric prefix using JavaScript parseFloat() semantics.
     *
     * @param mixed $value  Field value.
     * @param float $number Parsed number.
     * @return bool
     */
    public static function parse_number($value, &$number) {
        if (!preg_match('/^[\x09-\x0D\x20]*([+-]?(?:Infinity|(?:\d+\.?\d*|\.\d+)(?:[eE][+-]?\d+)?))/', (string) $value, $matches)) {
            $number = 0.0;
            return false;
        }

        if ('Infinity' === ltrim($matches[1], '+-')) {
            $number = (strpos($matches[1], '-') === 0) ? -INF : INF;
        } else {
            $number = (float) $matches[1];
        }

        return true;
    }

    /**
     * Match a value against a JavaScript-style validation expression.
     *
     * Accepts a bare body or a slash-delimited literal with supported flags.
     * Invalid expressions are skipped, matching the browser-side rule.
     *
     * @param string $expression Validation expression.
     * @param mixed  $value      Submitted value.
     * @return bool|null Match result, or null for an invalid expression.
     */
    public static function match_expression($expression, $value) {
        $body = (string) $expression;
        $flags = '';

        if (preg_match('#^/(.*)/([a-z]*)$#s', $body, $literal)) {
            $body = $literal[1];
            $flags = $literal[2];
        }

        if (preg_match('/[^dgimsuy]/', $flags) || count(array_unique(str_split($flags))) !== strlen($flags) || strpos($body, "\x01") !== false) {
            self::log_notice(sprintf('MetForm: invalid validation expression "%s" skipped.', $expression));
            return null;
        }

        $modifiers = 'u' . implode('', array_intersect(['i', 'm', 's'], str_split($flags)));
        if (strpos($flags, 'm') === false) {
            $modifiers .= 'D';
        }

        $body = self::translate_unicode_escapes($body, strpos($flags, 'u') !== false);
        if (strpos($flags, 'y') !== false) {
            $body = '\\A(?:' . $body . ')';
        }

        $pattern = "\x01" . $body . "\x01" . $modifiers;

        if (false === self::try_preg_match($pattern, '', $error)) {
            self::log_notice(sprintf('MetForm: invalid validation expression "%s" skipped (%s).', $expression, $error));
            return null;
        }

        $result = self::try_preg_match($pattern, (string) $value, $error);
        if (false === $result) {
            self::log_notice(sprintf('MetForm: validation expression "%s" could not be matched, value rejected (%s).', $expression, $error));
            return false;
        }

        return 1 === $result;
    }

    /**
     * Convert JavaScript Unicode escapes to PCRE equivalents.
     *
     * @param string $body         Pattern body.
     * @param bool   $unicode_mode Whether the JavaScript u flag is present.
     * @return string
     */
    private static function translate_unicode_escapes($body, $unicode_mode) {
        $body = preg_replace_callback(
            '/(?<!\\\\)((?:\\\\\\\\)*)\\\\u([dD][89aAbB][0-9a-fA-F]{2})\\\\u([dD][c-fC-F][0-9a-fA-F]{2})/',
            static function ($matches) {
                $high = hexdec($matches[2]);
                $low = hexdec($matches[3]);
                $codepoint = 0x10000 + (($high - 0xD800) << 10) + ($low - 0xDC00);

                return $matches[1] . '\\x{' . strtoupper(dechex($codepoint)) . '}';
            },
            $body
        );

        if ($unicode_mode) {
            $body = preg_replace('/(?<!\\\\)((?:\\\\\\\\)*)\\\\u\\{([0-9a-fA-F]{1,6})\\}/', '$1\\x{$2}', $body);
        }

        return preg_replace('/(?<!\\\\)((?:\\\\\\\\)*)\\\\u([0-9a-fA-F]{4})/', '$1\\x{$2}', $body);
    }

    /**
     * Execute a regex match and capture errors.
     *
     * @param string $pattern Regex pattern.
     * @param string $subject Subject string.
     * @param string $error   Error message (by reference).
     * @return int|false Match result.
     */
    private static function try_preg_match($pattern, $subject, &$error) {
        $error = '';
        $result = @preg_match($pattern, $subject);

        if (false === $result) {
            $error = function_exists('preg_last_error_msg')
                ? preg_last_error_msg()
                : 'Regex error code: ' . preg_last_error();
        }

        return $result;
    }

    /**
     * Write validation-expression notices only in WordPress debug mode.
     *
     * @param string $message Notice text.
     * @return void
     */
    private static function log_notice($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug-mode only notice.
            error_log($message);
        }
    }
}
