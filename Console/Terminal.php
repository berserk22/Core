<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Core\Console;

class Terminal {

    /**
     * @var int
     */
    private static int $width;

    /**
     * @var int
     */
    private static int $height;

    /**
     * @return int
     */
    public function getWidth(): int {
        $width = getenv('COLUMNS');
        if (false !== $width) {
            return (int) trim($width);
        }
        if (null === self::$width) {
            self::initDimensions();
        }
        return self::$width ?: 80;
    }

    /**
     * @return int
     */
    public function getHeight(): int {
        $height = getenv('LINES');
        if (false !== $height) {
            return (int) trim($height);
        }
        if (null === self::$height) {
            self::initDimensions();
        }
        return self::$height ?: 50;
    }

    /**
     * @return void
     */
    private static function initDimensions(): void {
        if ('\\' === DIRECTORY_SEPARATOR) {
            if (preg_match('/^(\d+)x(\d+)(?: \((\d+)x(\d+)\))?$/', trim(getenv('ANSICON')), $matches)) {
                // extract [w, H] from "wxh (WxH)"
                // or [w, h] from "wxh"
                self::$width = (int) $matches[1];
                self::$height = isset($matches[4]) ? (int) $matches[4] : (int) $matches[2];
            } elseif (null !== $dimensions = self::getConsoleMode()) {
                // extract [w, h] from "wxh"
                self::$width = (int) $dimensions[0];
                self::$height = (int) $dimensions[1];
            }
        } elseif ($sttyString = self::getSttyColumns()) {
            if (preg_match('/rows.(\d+);.columns.(\d+);/i', $sttyString, $matches) || preg_match('/;.(\d+).rows;.(\d+).columns/i', $sttyString, $matches)) {
                // extract [w, h] from "rows h; columns w;"
                self::$width = (int) $matches[2];
                self::$height = (int) $matches[1];
            }
        }
    }

    /**
     * @return array|null
     */
    private static function getConsoleMode(): ?array {
        if (!function_exists('proc_open')) {
            return null;
        }
        $descriptorspec = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open('mode CON', $descriptorspec, $pipes, null, null, array('suppress_errors' => true));
        if (is_resource($process)) {
            $info = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
            if (preg_match('/--------+\r?\n.+?(\d+)\r?\n.+?(\d+)\r?\n/', $info, $matches)) {
                return array((int) $matches[2], (int) $matches[1]);
            }
        }
        return null;
    }

    /**
     * @return false|string|null
     */
    private static function getSttyColumns(): false|string|null {
        if (!function_exists('proc_open')) {
            return null;
        }
        $descriptorspec = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open(
            'stty -a | grep columns',
            $descriptorspec,
            $pipes,
            null,
            null,
            array('suppress_errors' => true)
        );
        if (is_resource($process)) {
            $info = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
            return $info;
        }
        else {
            return null;
        }
    }

}
