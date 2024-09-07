<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Core\Common\Events;

interface Dispatcher {

    /**
     * @param $events
     * @param $listener
     * @return void
     */
    public function listen($events, $listener): void;

    /**
     * @param string $event
     * @return bool
     */
    public function hasListeners(string $event): bool;

    /**
     * @param string $event
     * @return array|bool
     */
    public function getListeners(string $event): array|bool;

    /**
     * @param $event
     * @param array $payload
     * @param bool $halt
     * @return mixed
     */
    public function dispatch($event, array $payload = [], bool $halt = false): mixed;

    /**
     * @param string $event
     * @return void
     */
    public function flush(string $event): void;

}
