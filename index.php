<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL);
require_once __DIR__ . DIRECTORY_SEPARATOR . 'PHPnow' . DIRECTORY_SEPARATOR . 'PHPnow.class.php';

$tpl = new \PHPnow;
$t = 'index';
$tpl->display($t);