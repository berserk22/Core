<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Core\Config;

interface Driver {

    /**
     * @param string $file
     * @return array
     */
    public function read(string $file): array;
}
