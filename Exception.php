<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Core;

use Throwable;

class Exception extends \Exception {

    /**
     * @var Throwable|Exception|null
     */
    protected Exception|Throwable|null $previous;

    /**
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null) {
        if ($previous !== null) {
            $this->previous = $previous;
        }
        parent::__construct($message, $code);
    }

    /**
     * @return string
     */
    public function __toString(): string {
        if (version_compare(PHP_VERSION, '8.0', '<') && $this->previous !== null) {
            return $this->previous->__toString() . "\n\nNext " . parent::__toString();
        }
        return parent::__toString();
    }
}
