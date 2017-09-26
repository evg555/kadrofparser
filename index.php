<?php

if (empty($_SESSION['cityname'])) $_SESSION['cityname'] = 'vladivostok';
if (empty($_SESSION['pageNumber'])) $_SESSION['pageNumber'] = 1;

function autoLoader($name) {
    require 'classes/'.$name.'.php';
}

spl_autoload_register('autoLoader');

require_once  __DIR__ . '/config.php';
require_once __DIR__ . '/lib/phpQuery/phpQuery.php';

if (!empty($parts)){
    foreach ($parts as $part){
        $controller = new MainController($siteUrl,$part,$lastDate);
        $controller->worker();
        break;
    }
}