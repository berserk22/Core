<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Core;

use Config\Modules;
use Core\Config\Config;
use Core\Events\Dispatcher;
use Core\Factory\ContainerFactory;
use Core\Handler\ErrorRenderer;
use Core\Module\Middleware;
use Core\Module\Provider;
use Core\Traits\App;
use Exception;
use Generator;
use InvalidArgumentException;
use LogicException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use Slim\Factory\AppFactory;
use Slim\Factory\ServerRequestCreatorFactory;

class Application {

    use App;

    const EVENT_BOOT = 'boot';

    const EVENT_INIT = 'init';

    const EVENT_REGISTER = 'register';

    /**
     * @var string
     */
    private string $projectDir = ROOT_DIR;

    /**
     * @var array
     */
    private array $modules = [];

    /**
     * @var bool
     */
    private bool $booted = false;

    /**
     * @var string development|production
     */
    public string $environment = 'development';

    /**
     * @var array
     */
    protected array $events = [
        self::EVENT_INIT => ['beforeInit', 'init', 'afterInit'],
        self::EVENT_BOOT => ['beforeBoot', 'boot', 'afterBoot'],
        self::EVENT_REGISTER => ['register'],
    ];

    /**
     * @var Dispatcher
     */
    protected Dispatcher $dispatcher;

    /**
     * @throws Exception
     */
    public function __construct() {
        $this->setContainer(ContainerFactory::createInstance($this->getSettings()));
        AppFactory::setContainer($this->getContainer());
        $this->setApp(AppFactory::create());
        $this->dispatcher = new Dispatcher();
        $this->handler();
        $this->getApp()->addMiddleware(new Middleware());

        $this->getApp()->getRouteCollector()->setCacheFile($this->getProjectDir()."cache/router/route.php");

        $config = $this->getContainer()->get('config')->getSetting('slim');

        $logger = new Logger($config['logger']['name']);

        $streamHandler = new StreamHandler(
            $this->getProjectDir().$config['logger']['path'].$config['logger']['name']."_".date("dmY").".log",
            $config['logger']['level']
        );
        $logger->pushHandler($streamHandler);
        $logger->useMicrosecondTimestamps(false);

        // Error Handler
        if ($config['customHandler']){
            $errorMiddleware = $this->getApp()->addErrorMiddleware(
                $config['displayErrorDetails'],
                $config['logError'],
                $config['logErrorDetails'],
                $logger
            );
            $errorHandler = $errorMiddleware->getErrorHandler('Exception');
            $errorHandler->registerErrorRenderer("text/html", new ErrorRenderer($this));
        }
        else {
            $this->getApp()->addErrorMiddleware(
                $config['displayErrorDetails'],
                $config['logError'],
                $config['logErrorDetails'],
                $logger
            );
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function getSettings(): array {
        $config = (new Config($this))->load($this->environment);
        date_default_timezone_set($config->getSetting()['slim']['timezone']);
        return ['config' => $config];
    }

    /**
     * @return string
     */
    public function getProjectDir() :string {
        return $this->projectDir;
    }

    public function registry(): Generator {
        $contents = Modules::getModules();
        return $this->generator($contents);
    }

    /**
     * @param array $contents
     * @return Generator
     */
    protected function generator(array $contents = []): Generator {
        foreach ($contents as $class) {
            yield new $class($this);
        }
    }

    /**
     * @return $this
     */
    public function handler(): static {
        $this->boot();
        foreach ($this->getModules() as $module) {
            /* @var $module Provider */
            $this->initializeModuleEvents($module, ['register']);
        }
        $this->fireModuleEvent($this->events[self::EVENT_REGISTER]);
        return $this;
    }

    /**
     * void
     */
    public function boot(): void {
        if ($this->booted === false){
            $this->initializeModules();
            foreach ($this->modules as $module) {
                $this->initializeModuleEvents($module, $this->events[self::EVENT_BOOT]);
            }
            $this->fireModuleEvent($this->events[self::EVENT_BOOT]);
            $this->booted = true;
        }
    }

    /**
     * void
     */
    protected function initializeModules(): void {
        foreach ($this->registry() as $module) {
            /* @var $module Provider */
            $name = $module->getName();
            if (isset($this->modules[$name])) {
                throw new LogicException(sprintf('Trying to register two bundles with the same name "%s"', $name));
            }
            $this->initializeModuleEvents($module, $this->events[self::EVENT_INIT]);
            $this->modules[$name] = $module;
        }
        $this->fireModuleEvent($this->events[self::EVENT_INIT]);
    }

    /**
     * @return array[] Provider
     */
    public function getModules() :array {
        return $this->modules;
    }

    /**
     * @param $name
     * @return Provider
     */
    public function getModule($name) :Provider {
        if (!isset($this->modules[$name])) {
            throw new InvalidArgumentException(
                sprintf('Module "%s" does not exist or it is not enabled. Maybe you forgot to
                 add it in the registerModules() method of your %s.php file?',
                    $name,
                    get_class($this)
                )
            );
        }
        return $this->modules[$name];
    }

    /**
     * @param Provider $module
     * @param array $events
     * @return void
     */
    protected function initializeModuleEvents(Provider $module, array $events): void {
        $reflection = new ReflectionClass(get_class($module));
        foreach ($events as $event) {
            if (!$reflection->hasMethod($event)) {
                continue;
            }
            $this->dispatcher->listen($event, $module);
        }
    }

    protected function fireModuleEvent(array $events): void {
        foreach ($events as $event) {
            $this->dispatcher->dispatch($event, [$this]);
        }
    }

    /**
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface {
        $serverRequestCreator = ServerRequestCreatorFactory::create();
        return $serverRequestCreator->createServerRequestFromGlobals();
    }

}
