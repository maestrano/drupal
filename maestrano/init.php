<?php

//-----------------------------------------------
// Define root folder and load base
//-----------------------------------------------
if (!defined('MAESTRANO_ROOT')) { define("MAESTRANO_ROOT", realpath(dirname(__FILE__))); }
if (!defined('ROOT_PATH')) { define('ROOT_PATH', realpath(dirname(__FILE__)) . '/../'); }
chdir(ROOT_PATH);

//-----------------------------------------------
// Load Maestrano library
//-----------------------------------------------
require_once ROOT_PATH . 'vendor/maestrano/maestrano-php/lib/Maestrano.php';
Maestrano::configure(ROOT_PATH . 'maestrano.json');

//-----------------------------------------------
// Require your app specific files here
//-----------------------------------------------
define('DRUPAL_ROOT', ROOT_PATH);
require_once ROOT_PATH . '/includes/bootstrap.inc';
// Make sure cookie domain is set to the right value
$cookie_domain = $_SERVER['HTTP_HOST'];
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);