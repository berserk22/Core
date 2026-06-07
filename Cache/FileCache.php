<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Core\Cache;

class FileCache {

    /**
     * @var string
     */
    private string $dir;

    /**
     * @param string $dir
     */
    public function __construct(string $dir) {
        $this->dir = rtrim($dir, '/');
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key): mixed {
        $file = $this->path($key);
        if (!file_exists($file)) {
            return null;
        }
        $data = @unserialize((string) file_get_contents($file));
        if (!is_array($data) || $data['expires'] < time()) {
            @unlink($file);
            return null;
        }
        return $data['value'];
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @return void
     */
    public function set(string $key, mixed $value, int $ttl = 3600): void {
        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0755, true);
        }
        file_put_contents(
            $this->path($key),
            serialize(['expires' => time() + $ttl, 'value' => $value]),
            LOCK_EX
        );
    }

    /**
     * @param string $key
     * @return void
     */
    public function delete(string $key): void {
        @unlink($this->path($key));
    }

    /**
     * @param string $key
     * @return string
     */
    private function path(string $key): string {
        return $this->dir . '/' . md5($key) . '.cache';
    }

}
