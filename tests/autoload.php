<?php

define('ELASTICSEARCH_HOST', getenv('ELASTICSEARCH_HOST') ?: '172.17.0.1:9222');
define('ELASTICSEARCH_USER', getenv('ELASTICSEARCH_USER') ?: 'elastic');
define('ELASTICSEARCH_PASSWORD', getenv('ELASTICSEARCH_PASSWORD') ?: 'elastic');

$_ENV['ELASTICSEARCH_HOST'] = ELASTICSEARCH_HOST;
$_ENV['ELASTICSEARCH_USER'] = ELASTICSEARCH_USER;
$_ENV['ELASTICSEARCH_PASSWORD'] = ELASTICSEARCH_PASSWORD;

date_default_timezone_set('Europe/Paris');

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/TestKernel.php';
