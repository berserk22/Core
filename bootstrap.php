<?php

use Core\Application;
use Modules\Database\Tracy\Panel;
use Tracy\Debugger;

require_once '../vendor/autoload.php';

ini_set('display_errors', 0);

date_default_timezone_set('Europe/Berlin');

$port = $_SERVER["SERVER_PORT"];
$server_name = $_SERVER["SERVER_NAME"];
$http = ($port==='443'?'https':'http').'://';

$domain = $server_name.($port!=='80'&&$port!=='443'?':'.$port:'');

header('Access-Control-Allow-Origin: '.$domain);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Requested-With, XMLHttpRequest');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE');

Debugger::enable(Debugger::Development);

$headers = require_once "../config/headers.php";
$headerCSP = "Content-Security-Policy:";
foreach ($headers as $key => $value){
    $headerCSP.=$key." ".$value;
}
header($headerCSP);


header("Strict-Transport-Security: max-age=600");
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Permissions-Policy: geolocation=(self "'.$http.$domain.'/"), microphone=()');
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");

define('DOMAIN_URI', $http.$domain);
define('ROOT_DIR', realpath(__DIR__.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR);
define('WEB_ROOT_DIR', realpath(__DIR__.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."www")."");

try {
    $application = new Application();
    $application->getApp()->run($application->getRequest());
    if ($application->getContainer()->has("database")){
        $capsule = $application->getContainer()->get("database");
        Debugger::getBar()->addPanel(new Panel($capsule->getConnection()->getQueryLog()));
    }
    if (isset($capsule)){
        $capsule->getConnection()->disconnect();
    }
    elseif ($application->getContainer()->has("database")){
        $capsule = $application->getContainer()->get("database");
        $capsule->getConnection()->disconnect();
    }
} catch (Exception $e) {
    echo "<pre>";
    var_dump([
        'line'=>$e->getLine(),
        'file'=>$e->getFile(),
        'code'=>$e->getCode(),
        'message'=>$e->getMessage(),
        //'trace'=>$e->getTrace()
    ]);
    echo "</pre>";
}
Debugger::getBar();
