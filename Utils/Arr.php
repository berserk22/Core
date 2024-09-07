<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Core\Utils;

class Arr {

    /**
     * @param $value
     * @return array
     */
    public static function wrap($value):array {
        if (is_null($value)) {
            return [];
        }
        return !is_array($value)?[$value]:$value;
    }

}
