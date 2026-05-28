<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Core\Module;

use Core\Config\Config;
use Core\Traits\App;
use Core\Utils\Helper;
use DI\DependencyException;
use DI\NotFoundException;
use Illuminate\Database\Capsule\Manager;
use Modules\MsgQueue\MsgQueue;
use Modules\Session\SessionManager;
use Modules\View\ViewManager;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Random\RandomException;
use Slim\Routing\RouteCollectorProxy;

abstract class Controller {

    use App;

    /**
     * @var Config|null
     */
    private ?Config $config = null;

    /**
     * @var Logger|null
     */
    private ?Logger $loggerInstance = null;

    /**
     * @var string
     */
    public string $contentType = "text/html";

    /**
     * @param RouteCollectorProxy|null $routeCollectorProxy
     * @return void
     */
    public function __invoke(?RouteCollectorProxy $routeCollectorProxy = null): void {
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
     * @return MsgQueue|null
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getQueue(): ?MsgQueue {
        if (!$this->getContainer()->has('MsgQueue\Queue')) {
            return null;
        }
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
        $queue = $this->getQueue();
        if ($queue === null) {
            return;
        }
        $queue->setMessage($event, $message);
    }

    /**
     * @return Manager|null
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getDB(): ?Manager {
        if (!$this->getContainer()->has('database')) {
            return null;
        }
        return $this->getContainer()->get('database');
    }

    /**
     * @return Logger
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getLogger(): Logger {
        if ($this->loggerInstance !== null) {
            return $this->loggerInstance;
        }
        if ($this->getContainer()->has('logger')) {
            $this->loggerInstance = $this->getContainer()->get('logger');
            return $this->loggerInstance;
        }
        $config = $this->getConfig('slim');
        $logDir = $this->getProjectDir() . $config['logger']['path'];
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $logger = new Logger($config['logger']['name']);
        $streamHandler = new StreamHandler(
            $logDir . $config['logger']['name'] . "_" . date("dmY") . ".log",
            $config['logger']['level']
        );
        $logger->pushHandler($streamHandler);
        $logger->useMicrosecondTimestamps(false);
        $this->loggerInstance = $logger;
        return $this->loggerInstance;
    }

    /**
     * @return string
     */
    private function getProjectDir(): string {
        return dirname(dirname(dirname(__DIR__))) . '/';
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

    /**
     * @return string
     * @throws RandomException
     */
    public function getUUID(): string {
        $data = random_bytes(16);
        // Set version to 0100 (UUID v4)
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        // Set bits 6-7 to 10 (variant)
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    abstract protected function registerFunctions();
}
