<?php

define('ELASTICSEARCH_HOST', getenv('ELASTICSEARCH_HOST') ?: '127.0.0.1:9222');
$_ENV['ELASTICSEARCH_HOST'] = ELASTICSEARCH_HOST;

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/TestKernel.php';
require __DIR__.'/Elasticsearch/_files/city.php';
require __DIR__.'/Elasticsearch/_files/user.php';
require __DIR__.'/Elasticsearch/_files/with_anonymouys_analyzer.php';
