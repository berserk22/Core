<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Core\Module;

use Core\Application;
use Core\Config\Config;
use Core\Traits\App;
use Core\Utils\Helper;
use DI\DependencyException;
use DI\NotFoundException;
use Illuminate\Database\Capsule\Manager;
use Modules\Elastic\ElasticSearch;
use Modules\MsgQueue\MsgQueue;
use Modules\Session\SessionManager;
use Modules\View\ViewManager;
use Slim\Routing\RouteCollectorProxy;

abstract class Controller {

    use App;

    /**
     * @var Config|null
     */
    private ?Config $config = null;

    /**
     * @param RouteCollectorProxy|null $routeCollectorProxy
     * @return void
     */
    public function __invoke(RouteCollectorProxy $routeCollectorProxy = null): void {
        $this->registerFunctions();
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getView(): ?ViewManager {
        if ($this->getContainer()->has('ViewManager::View')){
            return $this->getContainer()->get('ViewManager::View');
        }
        else {
            return null;
        }
    }

    /**
     * @return SessionManager|null
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getSession(): ?SessionManager{
        if ($this->getContainer()->has('Session\Manager')){
            return $this->getContainer()->get('Session\Manager');
        }
        else {
            return null;
        }
    }

    /**
     * @return Helper|string
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getHelper(): Helper|string {
        $helper = "Core\Helper";
        if (!$this->getContainer()->has($helper)){
            $this->getContainer()->set($helper, function(){
                return new Helper();
            });
        }
        return $this->getContainer()->get($helper);
    }

    /**
     * @return ElasticSearch|null
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getSearch(): ElasticSearch|null {
        if ($this->getContainer()->has('ElasticSearch')){
            return $this->getContainer()->get('ElasticSearch');
        }
        else {
            return null;
        }
    }

    /**
     * @return MsgQueue
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getQueue(): MsgQueue {
        return $this->getContainer()->get('MsgQueue\Queue');
    }

    /**
     * @param string $event
     * @param mixed $message
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function setMessage(string $event, mixed $message): void {
        $this->getQueue()->setMessage($event, $message);
    }

    /**
     * @return Manager
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getDB(): Manager {
        return $this->getContainer()->get('database');
    }

    /**
     * @param string $group
     * @param string $key
     * @return mixed
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getConfig(string $group = "", string $key = ""): mixed {
        if (is_null($this->config)){
            $this->config = $this->getContainer()->get("config");
        }
        $config = $this->config->getSetting();
        if (!empty($group)){
            if (!empty($key)){
                $config = $this->config->getSetting($group)[$key];
            }
            else {
                $config = $this->config->getSetting($group);
            }
        }
        return $config;
    }

    abstract protected function registerFunctions();
}
