<?php

if (empty($_SESSION['cityname'])) $_SESSION['cityname'] = 'vladivostok';
if (empty($_SESSION['pageNumber'])) $_SESSION['pageNumber'] = 1;

function autoLoader($name) {
    require 'classes/'.$name.'.php';
}

spl_autoload_register('autoLoader');

require_once  __DIR__ . '/config.php';
require_once __DIR__ . '/lib/phpQuery/phpQuery.php';
require_once __DIR__ . '/lib/PHPMailer/src/Exception.php';
require_once __DIR__ . '/lib/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/lib/PHPMailer/src/SMTP.php';

if (!empty($args)){
    try {
        $controller = new MainController($args);
        $controller->worker();
    } catch (Exception $e){
        $e->getMessage();
    }

}