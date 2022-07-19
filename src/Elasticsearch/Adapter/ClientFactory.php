<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Adapter;

use Elastic\Elasticsearch\ClientBuilder;

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
        return new ES8Client(ClientBuilder::fromConfig($config));
    }
}
