<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Core\Utils;

use Core\Exception;

class Curl {

    /**
     * @var string
     */
    protected static string $cookieFile = '';

    /**
     * @var string
     */
    protected static string $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 11_0_1) '.
        'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.141 Safari/537.36';

    /**
     * @var int
     */
    protected static int $maxRedirects = 20;

    /**
     * @var bool
     */
    protected static bool $followlocationAllowed = true;

    /**
     * @var string
     */
    protected static string $cookieString = "";

    protected static string $errorStr = "Curl error: ";

    /**
     * @throws Exception
     */
    public function __construct() {
        self::$cookieFile = dirname(__FILE__).'/../../data/cookies/cookies.txt';
        if (!file_exists(self::$cookieFile) || !is_writable(self::$cookieFile)) {
            throw new Exception('Cookie file missing or not writable.');
        }
        // check for PHP settings that unfits
        // correct functioning of CURLOPT_FOLLOWLOCATION
        if (ini_get('open_basedir') != '' || ini_get('safe_mode') == 'On') {
            self::$followlocationAllowed = false;
        }
    }

    /**
     * @param string $url
     * @param string|null $user
     * @param string|null $password
     * @return mixed
     * @throws Exception
     */
    public static function getFileInfo(string $url, string $user = null, string $password = null): mixed {
        $process = curl_init($url);

        self::setBasicOptions($process);
        curl_setopt($process, CURLOPT_FILETIME, true);
        curl_setopt($process, CURLOPT_NOBODY, true);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, true);

        if (null !== $user && null !== $password) {
            curl_setopt($process, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($process, CURLOPT_USERPWD, $user . ":" . $password);
        }

        curl_setopt($process, CURLOPT_HEADER, 0);

        if (self::$followlocationAllowed) {
            // if PHP settings allow it use AUTOMATIC REDIRECTION
            curl_setopt($process, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($process, CURLOPT_MAXREDIRS, self::$maxRedirects);
        } else {
            curl_setopt($process, CURLOPT_FOLLOWLOCATION, false);
        }

        $return = curl_exec($process);

        if ($return === false) {
            throw new Exception(self::$errorStr . curl_error($process));
        }

        $return = curl_getinfo($process, CURLINFO_FILETIME);
        if ($return != -1) { //otherwise unknown
            $return = date("Y-m-d H:i:s", $return); //etc
        }

        $code = curl_getinfo($process, CURLINFO_HTTP_CODE);
        curl_close($process);
        if ($code == 301 || $code == 302) {
            $location = self::parseRedirectionHeader($url);
            return self::get($location);
        }
        return $return;
    }

    /**
     * @param string $url
     * @param string|null $user
     * @param string|null $password
     * @return string|array|bool|string[]|null
     * @throws Exception
     */
    public static function get(
        string $url,
        string $user = null,
        string $password = null
    ): string|array|bool|null {
        $process = curl_init($url);
        self::setBasicOptions($process);
        curl_setopt($process, CURLOPT_HEADER, 1);
        if (null !== $user && null !== $password) {
            curl_setopt($process, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($process, CURLOPT_USERPWD, $user . ":" . $password);
        }
        if (self::$followlocationAllowed) {
            curl_setopt($process, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($process, CURLOPT_MAXREDIRS, self::$maxRedirects);
        } else {
            curl_setopt($process, CURLOPT_FOLLOWLOCATION, false);
        }
        $return = curl_exec($process);

        if ($return === false) {
            throw new Exception(self::$errorStr . curl_error($process));
        }
        $code = curl_getinfo($process, CURLINFO_HTTP_CODE);
        curl_close($process);
        if ($code == 301 || $code == 302) {
            $location = self::parseRedirectionHeader($url);
            return self::get($location);
        }
        return $return;
    }

    /**
     * @param array $cookie
     * @return void
     */
    public function setCookie(array $cookie = []): void {
        self::$cookieString = "";
        foreach($cookie as $key => $value ) {
            self::$cookieString .= "$key=$value;";
        }
    }

    /**
     * @param $process
     * @return void
     */
    private static function setBasicOptions($process): void {
        curl_setopt($process, CURLOPT_USERAGENT, self::$userAgent);
        curl_setopt($process,CURLOPT_COOKIE, self::$cookieString);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
    }

    /**
     * @param string $url
     * @return string
     * @throws Exception
     */
    private static function parseRedirectionHeader(string $url): string {
        $process = curl_init($url);
        self::setBasicOptions($process);
        curl_setopt($process, CURLOPT_HEADER, 1);
        $return = curl_exec($process);
        if ($return === false) {
            throw new Exception(self::$errorStr . curl_error($process));
        }
        curl_close($process);
        if (!preg_match('#Location: (.*)#', $return, $location)) {
            throw new Exception('No Location found');
        }
        if (self::$maxRedirects-- <= 0) {
            throw new Exception('Max redirections reached trying to get: ' . $url);
        }
        return trim($location[1]);
    }

}
