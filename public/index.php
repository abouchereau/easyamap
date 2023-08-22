<?php

use App\Kernel;
use App\Util\Amap;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';
require_once __DIR__.'/../config/maintenance.php';



umask(0000);

return function (array $context) {
    require __DIR__.'/../config/url2env.php';
    $amap = new Amap($url2env);//trouve l'amap en fonction de l'URL
    $amap->checkMaintenance(IP);
    $context['APP_ENV'] = $amap->getName();
    $context['APP_DEBUG'] =  $amap->getDebug();

    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
