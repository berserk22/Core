<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Core\Module;

use Core\Traits\App;
use DI\DependencyException;
use DI\NotFoundException;

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
                    $routerMethods->map($this->routerType, [$group['method']], $group['instance']);
                }
                else {
                    $routerMethods->map($this->routerType, [$this->router], $this->controller);
                }
            }
            else {
                $this->getApp()->group($this->router, $this->controller);
            }
        } catch (\Exception $ex) {
            die ($ex->getMessage());
        }
    }

    /**
     * @param $controller
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getMapBuilder($controller): void {
        foreach ($this->mapForUriBuilder as $key => $value) {
            $route_add = false;
            if ($this->getApcuCache() !== null){
                $routers = $this->getApcuCache()->get('routers');
                if (isset($routers[$this->routerType.'_'.strtolower($key)])){
                    $route = $routers[$this->routerType.'_'.strtolower($key)][0];
                    $this->getApp()->map(
                        $route['method'],
                        $route['route'],
                        [$route['class'], $route['action']]
                    )->setName($this->routerType.'_'.strtolower($key));
                    $route_add = true;
                }
            }
            if ($route_add !== true){
                $this->getApp()->map(
                    $value['method'],
                    $this->router.$value['pattern'],
                    [$controller, $value['callback']]
                )->setName($this->routerType.'_'.strtolower($key));
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
            if(str_contains($routers[$type][0]['route'], '{')){
                preg_match('/{([^*]+)}/', $routers[$type][0]['route'], $match);
                $tmp_key = explode(':', $match[1])[0];
                return str_replace($match[0], $obj[$tmp_key], $routers[$type][0]['route']);
            }
            else {
                return $routers[$type][0]['route'];
            }
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
