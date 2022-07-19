<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Adapter;

use Elastic\Elasticsearch\ClientBuilder as ES8ClientBuilder;
use Elasticsearch\ClientBuilder as ES7ClientBuilder;

/**
 * Factory for elasticsearch client adapter
 */
final class ClientFactory
{
    /**
     * Create an elasticsearch client from config array
     */
    public static function fromArray(array $config): ClientInterface
    {
        if (class_exists(ES8ClientBuilder::class)) {
            return new ES8Client(ES8ClientBuilder::fromConfig($config));
        }

        if (class_exists(ES7ClientBuilder::class)) {
            return new ES7Client(ES7ClientBuilder::fromConfig($config));
        }

        throw new \LogicException('Cannot found any supported elasticsearch driver');
    }
}
