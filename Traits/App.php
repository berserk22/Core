<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Core\Traits;

use DI\Container;

trait App {

    /**
     * @var \Slim\App
     */
    protected \Slim\App $app;

    /**
     * @var Container
     */
    protected Container $container;

    public function __construct($application = null){
        if (!is_null($application)){
            $this->setApp($application->getApp());
            $this->setContainer($application->getContainer());
        }
        return $this;
    }

    /**
     * @param \Slim\App $application
     * @return void
     */
    public function setApp(\Slim\App $application): void {
        $this->app = $application;
    }

    /**
     * @return \Slim\App
     */
    public function getApp(): \Slim\App {
        return $this->app;
    }

    public function setContainer($container): void {
        $this->container = $container;
    }

    /**
     * @return null|Container
     */
    public function getContainer(): ?Container {
        return $this->container;
    }

}
