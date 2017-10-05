<?php

require_once  __DIR__ . '/config.php';
require_once __DIR__ . '/lib/phpQuery/phpQuery.php';
require_once __DIR__ . '/lib/PHPMailer/src/Exception.php';
require_once __DIR__ . '/lib/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/lib/PHPMailer/src/SMTP.php';
//require_once __DIR__ . '/lib/PHPLogger/Directory.php';
//require_once __DIR__ . '/lib/PHPLogger/Storage.php';
//require_once __DIR__ . '/lib/PHPLogger/Logger.php';


spl_autoload_register('autoLoaderCore');

if (!empty($args)){
    try {
        $controller = new MainController($args);
        $controller->worker();
    } catch (Exception $e){
        $e->getMessage();
    }

}

function autoLoaderCore($name) {
    require 'classes/'.$name.'.php';
}
