<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Core\Console;

class Color {

    const TEXT_BLACK = '0;30';
    const TEXT_DARK_GRAY = '1;30';
    const TEXT_RED = '0;31';
    const TEXT_LIGHT_RED = '1;31';
    const TEXT_GREEN = '0;32';
    const TEXT_LIGHT_GREEN = '1;32';
    const TEXT_BROW = '0;33';
    const TEXT_YELLOW = '1;33';
    const TEXT_BLUE = '0;34';
    const TEXT_LIGHT_BLUE = '1;34';
    const TEXT_PURPLE = '0;35';
    const TEXT_LIGHT_PURPLE = '1;35';
    const TEXT_CYAN = '0;36';
    const TEXT_LIGHT_CYAN = '1;36';
    const TEXT_LIGHT_GRAY = '0;37';
    const TEXT_WHITE = '1;37';

    const BACKGROUND_BLACK = '40';
    const BACKGROUND_RED = '41';
    const BACKGROUND_GREEN = '42';
    const BACKGROUND_YELOW = '43';
    const BACKGROUND_BLUE = '44';
    const BACKGROUND_MAGENTA = '45';
    const BACKGROUND_CYAN = '46';
    const BACKGROUND_LIGHT_GRAY = '47';

    /**
     * @param string $string
     * @param string|null $text_color
     * @param string|null $background_color
     * @return string
     */
    public static function getColoredString(
        string $string,
        string $text_color = null,
        string $background_color = null
    ): string {
        $colored_string = "";
        if ($text_color !== null) {
            $colored_string .= "\033[" . $text_color . "m";
        }
        if ($background_color !== null) {
            $colored_string .= "\033[" . $background_color . "m";
        }
        $colored_string .=  $string . "\033[0m";
        return $colored_string;
    }

}
