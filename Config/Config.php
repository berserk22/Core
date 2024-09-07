<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Core\Config;

use Core\Application;
use Core\Config\Driver\Ini;
use Core\Config\Driver\Json;

class Config implements \ArrayAccess, \Iterator, \Countable {

    const FILE = 1;

    const EXTENSION = 2;

    /**
     * @var int
     */
    protected int $pointer = 0;

    /**
     * @var array
     */
    protected array $settings = [];

    /**
     * @var array
     */
    protected array $parser = [
        'ini' => Ini::class,
        'json' => Json::class,
    ];

    /**
     * @var array
     */
    protected array $ignore = [
        self::EXTENSION => ['.', '..', ''],
        self::FILE => [
            'headers.php',
            'modules.php',
            'Modules.php'
        ]
    ];

    /**
     * @var Application
     */
    private Application $app;

    /**
     * Config constructor.
     * @param Application $application
     * @param array $ignoreFile
     */
    public function __construct(Application $application, array $ignoreFile = []) {
        $this->app = $application;
        $this->ignore[self::FILE] = array_merge($this->ignore[self::FILE], $ignoreFile);
    }

    /**
     * @return string
     */
    protected function getPath():string {
        return $this->app->getProjectDir() . 'config/';
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getSetting(string $key = ''):mixed {
        return (!empty($key))?$this->settings[$key]:$this->settings;
    }

    /**
     * @param string $environment
     * @return Config
     * @throws Exception|\Exception
     */
    public function load(string $environment='development'):Config {
        $config = [];
        if (!is_readable($this->getPath())) {
            throw new Exception("Cannot read from config path!  Does it exist?  Is it readable?");
        }
        foreach (scandir($this->getPath()) as $file) {
            $extension = pathinfo($this->getPath().$file, PATHINFO_EXTENSION);
            if (in_array($extension, $this->ignore[self::EXTENSION])) {
                continue;
            }
            if (in_array($file, $this->ignore[self::FILE])) {
                continue;
            }
            if (substr($file, (strlen($file) - strlen('.dist' . $extension))) == '.dist' . $extension) {
                continue;
            }
            $config = $this->mergeConfigsVars(
                $config,
                $this->parseFile($this->getPath().$file, $extension, $environment)
            );
        }
        $this->settings = $this->group($this->mergeConfigsVars($this->settings, $config), '.');

        return $this;
    }

    /**
     * @param $file
     * @param null $extension
     * @param string $environment
     * @return array
     * @throws \Exception
     */
    public function parseFile($file, $extension = null, string $environment = 'development'):array {
        if (!is_readable($file)) {
            throw new Exception("Cannot read from the config file: $file");
        }
        $class = new $this->parser[$extension]();
        $raw = $this->parseSettings($class->read($file), ':');
        if (!isset($raw[$environment])) {
            throw new Exception("Unknown environment '$environment'");
        }
        $merged = $this->merge($raw, $environment);
        if (count($merged) == 0) {
            $merged = $raw[$environment]['settings'];
        }
        return $merged;
    }

    /**
     * @param array $parsed
     * @param string $environment
     * @return mixed
     * @throws \Exception
     */
    protected function merge(array $parsed, string $environment):array {
        $current = $parsed[$environment];
        $merged = $current['settings'];

        while ($current['parent'] !== null) {
            if (!array_key_exists($current['parent'], $parsed)) {
                throw new Exception("Invalid parent environment '
                {$current['parent']}' for environment '{$current['name']}'");
            }

            $parent = $parsed[$current['parent']];
            $merged = array_merge($parent['settings'], $merged);
            $current = $parent;
        }

        return $merged;
    }

    /**
     * @param array $raw
     * @param string $separator
     * @return array
     */
    protected function group(array $raw, string $separator = '.'): array {
        $group = [];
        foreach ($raw as $name => $settings) {
            list($name, $parent) = explode($separator, $name);
            $group[$name][$parent] = $this->parseValue($settings);
        }
        return $group;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function parseValue(mixed $value): mixed {
        if (is_bool($value) || ($value === '1' || $value === '0')){
            return boolval($value);
        }
        else {
            return $value;
        }
    }

    /**
     * @param array $raw
     * @param string $separator
     * @return array
     */
    protected function parseSettings(array $raw, string $separator = ':'):array {
        $parsed = [];
        foreach ($raw as $name => $settings) {
            if (!str_contains($name, $separator)) {
                $name = trim($name);
                $parsed[$name] = ['name' => trim($name), 'settings' => $settings, 'parent' => null];
                continue;
            }
            list($name, $parent) = explode($separator, $name);
            $name = trim($name);
            $parent = trim($parent);
            if (isset($parsed[trim($parent)])) {
                $parsed[$parent]['parent'] = $name;
            }
            if (isset($parsed[$name]['settings'])) {
                $parsed[$name] = [
                    'name' => $name,
                    'settings' => array_merge($parsed[$name]['settings'], $settings),
                    'parent' => null
                ];
            }
            if (isset($parsed[$parent]['settings'])) {
                $parsed[$parent] = [
                    'name' => $name,
                    'settings' => array_merge($parsed[$parent]['settings'], $settings),
                    'parent' => null
                ];
            }
        }
        return $parsed;
    }

    /**
     * @param array $arr1
     * @param array $arr2
     * @return array
     */
    protected function mergeConfigsVars(array $arr1, array $arr2):array {
        foreach ($arr2 as $key => $value) {
            if (array_key_exists($key, $arr1) && is_array($value)){
                $arr1[$key] = $this->mergeConfigsVars($arr1[$key], $value);
                continue;
            }
            $arr1[$key] = $value;
        }
        return $arr1;
    }

    /**
     * @return int
     */
    public function current(): int {
        return $this->pointer;
    }

    /**
     * @return void
     */
    public function next(): void {
        ++$this->pointer;
    }

    /**
     * @return mixed
     */
    public function key(): mixed {
        $keys = array_keys($this->settings);
        return $keys[$this->pointer];
    }

    /**
     * @return bool
     */
    public function valid(): bool {
        $values = array_values($this->settings);
        return isset($values[$this->pointer]);
    }

    /**
     * @return void
     */
    public function rewind(): void {
        $this->pointer = 0;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool {
        return true;
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed {
        return '';
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void {
        throw new Exception("configuration is immutable!");
    }

    /**
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void {
        throw new Exception("configuration is immutable!");
    }

    /**
     * @return int
     */
    public function count(): int {
        return count($this->settings);
    }

}
