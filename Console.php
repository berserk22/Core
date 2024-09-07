<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Core;

use Core\Console\Command;
use Core\Module\Provider;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;

class Console extends Application {

    /**
     * @var \Symfony\Component\Console\Application
     */
    protected \Symfony\Component\Console\Application $console;

    /**
     * @param string|null $name
     * @param string|null $version
     * @throws DependencyException
     * @throws NotFoundException
     * @throws Exception
     */
    public function __construct(?string $name = 'UNKNOWN', ?string $version = 'UNKNOWN') {
        parent::__construct();
        $config = $this->getContainer()->get('config')->getSetting('app');
        if (!isset($name) || $name === "UNKNOWN"){
            $name = $config['name'];
        }
        if (!isset($version) || $version === "UNKNOWN"){
            $version = $config['version'];
        }
        $this->console = new \Symfony\Component\Console\Application($name, $version);
    }

    /**
     * @return $this
     */
    public function init():Console {
        foreach ($this->registry() as $module) {
            /* @var $module Provider*/
            $this->initializeModuleEvents($module, $this->events[self::EVENT_INIT]);
            $this->implementCommand($module->console());
        }
        $this->fireModuleEvent($this->events[self::EVENT_INIT]);
        return $this;
    }

    /**
     * @param array $commands
     * @return void
     */
    protected function implementCommand(array $commands): void {
        foreach ($this->Generator($commands) as $command) {
            /* @var $command Command */
            $this->console->add($command);
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function exec(): void {
        $this->console->run();
    }
}
