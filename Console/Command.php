<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Core\Console;


use Core\Traits\App;

abstract class Command extends \Symfony\Component\Console\Command\Command {

    use App;

    /**
     * @param $application
     */
    public function __construct($application) {
        $this->setApp($application->getApp());
        $this->setContainer($application->getContainer());
        parent::__construct($this->getName());
    }

    /**
     * @param string $str
     * @param string|null $background_color
     * @return void
     */
    public function error(string $str, string $background_color = null): void {
        echo Color::getColoredString($str, Color::TEXT_RED, $background_color)."\n";
    }

    /**
     * @param string $str
     * @param string|null $background_color
     * @return void
     */
    public function success(string $str, string $background_color = null): void {
        echo Color::getColoredString($str, Color::TEXT_LIGHT_GREEN, $background_color)."\n";
    }

    /**
     * @param string $str
     * @param string|null $background_color
     * @return void
     */
    public function info(string $str, string $background_color = null): void {
        echo Color::getColoredString($str, Color::TEXT_LIGHT_BLUE, $background_color)."\n";
    }

    /**
     * @param string $str
     * @param string|null $background_color
     * @return void
     */
    public function warning(string $str, string $background_color = null): void {
        echo Color::getColoredString($str, Color::TEXT_YELLOW, $background_color)."\n";
    }

    /**
     * @param string $str
     * @param string $color
     * @param string|null $background_color
     * @return void
     */
    public function default(string $str, string $color = Color::TEXT_WHITE, string $background_color = null): void {
        echo Color::getColoredString($str, $color, $background_color)."\n";
    }

}
