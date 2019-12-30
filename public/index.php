<?php

use App\Kernel;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;
use App\Util\Amap;

require_once __DIR__.'/../config/url2env.php';
require_once __DIR__.'/../config/maintenance.php';


require dirname(__DIR__).'/config/bootstrap.php';

$amap = new Amap($url2env);//trouve l'amap en fonction de l'URL
$amap->checkMaintenance(IP);

if ($amap->getDebug()) {
    umask(0000);

    Debug::enable();
}

if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? $_ENV['TRUSTED_PROXIES'] ?? false) {
    Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST);
}

if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? $_ENV['TRUSTED_HOSTS'] ?? false) {
    Request::setTrustedHosts([$trustedHosts]);
}
//$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$_SERVER['APP_ENV'] = $amap->getName();
$kernel = new Kernel($amap->getName(), $amap->getDebug());
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
