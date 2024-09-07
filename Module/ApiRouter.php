<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Core\Module;

use DI\DependencyException;
use DI\NotFoundException;

class ApiRouter extends Router {

    /**
     * @var int
     */
    public int $version;

    public string $apiPath = "/api/v";

    /**
     * @return void
     */
    public function init(): void {
        try {
            if ($this->getContainer()->has('Router\Methods')){
                $routerMethods = $this->getContainer()->get('Router\Methods');
                $groups = $routerMethods->getAllGroups();
                if (isset($groups["v".$this->version."_".$this->routerType])){
                    $group = $groups[$this->routerType];
                    $routerMethods->map(
                        "v".$this->version."_".$this->routerType,
                        [$this->apiPath.$this->version."/".$group['method']],
                        $group['instance']
                    );
                }
                else {
                    $routerMethods->map(
                        "v".$this->version."_".$this->routerType,
                        [$this->apiPath.$this->version."/".$this->routerType],
                        $this->controller
                    );
                }
            }
            else {
                $this->getApp()->group($this->apiPath.$this->version."/".$this->routerType, $this->controller);
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
                if (isset($routers["api_v".$this->version."_".$this->routerType.'_'.strtolower($key)])){
                    $route = $routers["api_v".$this->version."_".$this->routerType.'_'.strtolower($key)][0];
                    $this->getApp()->map(
                        $route['method'],
                        $route['route'],
                        [$route['class'], $route['action']]
                    )->setName("api_v".$this->version."_".$this->routerType.'_'.strtolower($key));
                    $route_add = true;
                }
            }
            if ($route_add !== true){
                $this->getApp()->map(
                        $value['method'],
                        "/api/v".$this->version."/".$this->routerType.$value['pattern'],
                        [$controller, $value['callback']]
                )->setName("api_v".$this->version."_".$this->routerType.'_'.strtolower($key));
            }
        }
    }
}
