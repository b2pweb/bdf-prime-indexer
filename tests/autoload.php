<?php

if (!isset($_ENV['ELASTICSEARCH_HOST'])) {
    $_ENV['ELASTICSEARCH_HOST'] = '127.0.0.1:9222';
}

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/TestKernel.php';
