<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Core\Config\Driver;

use Core\Config\Driver;

class Json implements Driver {

    /**
     * @param string $file
     * @return array
     */
    public function read(string $file): array {
        $result = json_decode(file_get_contents($file), true);
        return $result ?: [];
    }
}
