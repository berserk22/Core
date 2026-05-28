<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Core\Module;

use Core\Traits\App;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;

class Router {

    use App;

    public string $routerType;

    /**
     * @var string
     */
    public string $router;

    /**
     * @var array
     */
    public array $mapForUriBuilder;

    /**
     * @var string
     */
    public string $controller;

    /**
     * @return void
     */
    public function init(): void {
        try {
            if ($this->getContainer()->has('Router\Methods')){
                $routerMethods = $this->getContainer()->get('Router\Methods');
                $groups = $routerMethods->getAllGroups();
                if (isset($groups[$this->routerType])){
                    $group = $groups[$this->routerType];
                    $this->router = $group['method'];
                    $routerMethods->map($this->routerType, [$group['method']], $this->controller);
                }
                else {
                    $routerMethods->map($this->routerType, [$this->router], $this->controller);
                }
            }
            else {
                $this->getApp()->group($this->router, $this->controller);
            }
        } catch (Exception $ex) {
            die($ex->getMessage());
        }
    }

    /**
     * @param string|object $controller
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getMapBuilder(string|object $controller): void {
        if (is_object($controller)) {
            $controller = get_class($controller);
        }

        if (!class_exists($controller)) {
            throw new \InvalidArgumentException(
                sprintf('Controller class "%s" does not exist.', $controller)
            );
        }

        foreach ($this->mapForUriBuilder as $key => $value) {
            $cacheKey = $this->routerType . '_' . strtolower($key);
            $routeAdded = false;
            if ($this->getApcuCache() !== null) {
                // get() возвращает сразу массив роутеров — не объект
                $routers = $this->getApcuCache()->get('routers'); // ← массив или null
                // Защита если кеш пустой
                if (!is_array($routers)) {
                    $routers = [];
                }
                if (isset($routers[$cacheKey])) {
                    $route = $routers[$cacheKey][0];
                    if ($route['class'] !== $controller) {
                        // DB has outdated class — use DB URL but current controller/action
                        $this->getApp()->map(
                            $route['method'],
                            $route['route'],
                            [$controller, $value['callback']]
                        )->setName($cacheKey);
                    } else {
                        $this->getApp()->map(
                            $route['method'],
                            $route['route'],
                            [$route['class'], $route['action']]
                        )->setName($cacheKey);
                    }
                    $routeAdded = true;
                }
            }
            if ($routeAdded !== true) {
                $router = $this->getApp()->map(
                    $value['method'],
                    $this->router . $value['pattern'],
                    [$controller, $value['callback']]
                )->setName($cacheKey);
                if (isset($value['middleware'])) {
                    $factory = $this->getContainer()->get('MiddlewareFactory');
                    foreach ((array)$value['middleware'] as $mw) {
                        $middleware = $factory->create($mw);
                        if ($middleware) {
                            $router->add($middleware);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param string $type
     * @param array $obj
     * @return string
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getUrl(string $type, array $obj = []): string {
        $routers = $this->getApcuCache()->get('routers');
        if (isset($routers[$type])){
            $route = $routers[$type][0]['route'];
            if (str_contains($route, '{')) {
                $route = preg_replace_callback(
                    "/{(\w+):[^\}]+}/",
                    function ($matches) use ($obj) {
                        $paramName = $matches[1];
                        return $obj[$paramName] ?? $matches[0];
                    },
                    $route
                );
            }
            return $route;
        }
        else {
            return $this->getApp()->getRouteCollector()->getRouteParser()->urlFor($type, $obj);
        }
    }

    /**
     * @return mixed
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getApcuCache(): mixed {
        if ($this->getContainer()->has('Router\ApcuCache')){
            return $this->getContainer()->get('Router\ApcuCache');
        }
        return null;
    }

}
