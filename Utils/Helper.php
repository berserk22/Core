<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Core\Utils;

use Tracy\Debugger;
use Tracy\Dumper;

class Helper {

    public function __construct(){
        Debugger::enable(Debugger::Production);
        Debugger::$showBar = false;
    }

    /**
     * @param mixed $var
     * @return void
     */
    public function dump(mixed $var): void {
        Dumper::dump($var, [
            Dumper::LOCATION=>true
        ]);
    }


}
