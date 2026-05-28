<?php

use Core\Application;
use Modules\Database\Tracy\Panel;
use Tracy\Debugger;

require_once __DIR__ . '/../vendor/autoload.php';

ini_set('display_errors', 0);

date_default_timezone_set('Europe/Berlin');

$port = $_SERVER["SERVER_PORT"];
$server_name = $_SERVER["SERVER_NAME"];
$http = ($port==='443'?'https':'http').'://';

$domain = $server_name.($port!=='80'&&$port!=='443'?':'.$port:'');

if (!defined('DOMAIN_URI')) {
    define('DOMAIN_URI', $http.$domain);
}
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', realpath(__DIR__.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR);
}
if (!defined('WEB_ROOT_DIR')) {
    define('WEB_ROOT_DIR', realpath(__DIR__.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."www")."");
}

ini_set('session.save_path', ROOT_DIR.'data/session');

if (PHP_SAPI !== 'cli') {
    header('Access-Control-Allow-Origin: https://'.$domain);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Requested-With, XMLHttpRequest');
    header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE');

    /*$tracyMode = ($_ENV['APP_ENV'] ?? 'production') === 'development'
        ? Debugger::Development
        : Debugger::Production;*/

    $tracyMode = Debugger::Production;

    Debugger::enable($tracyMode, __DIR__ . '/../data/log');

    $headers = require_once __DIR__ . "/../config/headers.php";
    $headerCSP = "Content-Security-Policy:";
    foreach ($headers as $key => $value){
        $headerCSP.=$key." ".$value;
    }
    header(trim($headerCSP));

    header("Strict-Transport-Security: max-age=31536000");
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: geolocation=(self "'.$http.$domain.'/"), microphone=()');
    header("X-Frame-Options: SAMEORIGIN");
    header("X-XSS-Protection: 1; mode=block");
}

if (!defined('PHPUNIT_RUNNING')) {

    $application = new Application();

    try {
        // Вариант 2: страховочный shutdown handler
        // Сработает при die(), fatal error, или если finally не отработал
        register_shutdown_function(function () use ($application): void {
            $application->terminate();
        });

        $application->getApp()->run($application->getRequest());

        // Tracy DB панель — только если БД подключена
        if ($application->getContainer()->has('database')) {
            $capsule = $application->getContainer()->get('database');
            Debugger::getBar()->addPanel(
                new Panel($capsule->getConnection()->getRawQueryLog())
            );
        }

    } catch (\Exception $e) {
        echo "<pre>";
        var_dump([
            'line'    => $e->getLine(),
            'file'    => $e->getFile(),
            'code'    => $e->getCode(),
            'message' => $e->getMessage(),
        ]);
        echo "</pre>";

    } finally {
        // Вариант 3: явный вызов через finally
        // Выполняется всегда — даже при исключении
        if ($application instanceof Application) {
            $application->terminate();
        }
    }

    Debugger::getBar();
}
