<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Core\Utils;

class Str {

    /**
     * @param $pattern
     * @param $value
     * @return bool
     */
    public static function is($pattern, $value):bool {
        $patterns = is_array($pattern) ? $pattern : (array) $pattern;
        if (!empty($patterns)) {
            foreach ($patterns as $pattern) {
                if ($pattern == $value) {
                    return true;
                }
                $pattern = preg_quote($pattern, '#');
                $pattern = str_replace('\*', '.*', $pattern);
                if (preg_match('#^'.$pattern.'\z#u', $value) === 1) {
                    return true;
                }
            }

        }
        return false;
    }

    /**
     * @param string $haystack
     * @param $needles
     * @return bool
     */
    public static function contains(string $haystack, $needles):bool {
        foreach ((array)$needles as $needle) {
            if ($needle !== '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $callback
     * @param null $default
     * @return array
     */
    public static function parseCallback($callback, $default = null):array {
        return static::contains($callback, '@')?explode('@', $callback, 2):[$callback, $default];
    }
}
