<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Core\Events;

use Core\Utils\Arr;
use Core\Utils\Str;
use Core\Common\Events;

class Dispatcher implements Events\Dispatcher {

    /**
     * @var array
     */
    protected array $listeners = [];

    /**
     * @var array
     */
    protected array $wildcards = [];

    /**
     * @param $events
     * @param $listener
     * @return void
     */
    public function listen($events, $listener): void {
        foreach ((array)$events as $event) {
            $this->listeners[$event][] = $listener;
        }
    }

    /**
     * @param string $event
     * @return bool
     */
    public function hasListeners(string $event): bool {
        return isset($this->listeners[$event]) || isset($this->wildcards[$event]);
    }

    /**
     * @param string $event
     * @return array|bool
     */
    public function getListeners(string $event): array|bool {
        $listener = $this->listeners[$event] ?? [];
        $listener = array_merge($listener, $this->getWildcardListeners($event));
        return class_exists($event, false)?:$listener;
    }

    /**
     * @param $event
     * @param array $payload
     * @param bool $halt
     * @return mixed
     */
    public function dispatch($event, array $payload = [], bool $halt = false): mixed {
        list($event, $payload) = $this->parseEventAndPayload($event, $payload);
        $responses = [];
        foreach ($this->getListeners($event) as $listener) {
            $response = call_user_func_array([$listener, $event], $payload);
            if ($halt && !is_null($response)) {
                return $response;
            }
            if (false === $response) {
                break;
            }
            $responses[] = $response;
        }
        return $halt?null:$responses;
    }

    /**
     * @param string $event
     * @return void
     */
    public function flush(string $event): void {}

    /**
     * @param $listener
     * @return array
     */
    protected function parseClassCallable($listener):array {
        return Str::parseCallback($listener, 'handler');
    }

    /**
     * @param string $event
     * @return array
     */
    public function getWildcardListeners(string $event): array {
        $wildcards = [];
        foreach ($this->wildcards as $key => $listener) {
            if (Str::is($key, $event)) {
                $wildcards = array_merge($wildcards, $listener);
            }
        }
        return $wildcards;
    }

    /**
     * @param $event
     * @param $payload
     * @return array
     */
    protected function parseEventAndPayload($event, $payload): array {
        if (is_object($event)) {
            list($payload, $event) = [[$event], get_class($event)];
        }
        return [$event, Arr::wrap($payload)];
    }

}
