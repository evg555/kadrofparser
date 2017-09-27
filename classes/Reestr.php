<?php

class Reestr
{
    private $_data = array();
    private static $_instance;

    public static function getInstance(){
        if (!self::$_instance instanceof self){
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    private function __construct() {}

    private function __clone() {}

    public function setData($key,$data){
        $this->_data[$key] = $data;
    }

    public function getData(){
        return $this->_data;
    }
}