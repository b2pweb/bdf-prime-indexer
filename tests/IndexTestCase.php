<?php

namespace Bdf\Prime\Indexer;

use Bdf\Prime\Indexer\Elasticsearch\ElasticsearchIndex;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchIndexConfigurationInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapper;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use PHPUnit\Framework\TestCase;

class IndexTestCase extends TestCase
{
    /**
     * @var Client
     */
    protected static $client;

    /**
     * @var string|null
     */
    protected static $esVersion = null;

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        self::getClient();
    }

    public static function getClient(): Client
    {
        if (self::$client) {
            return self::$client;
        }

        return self::$client = ClientBuilder::fromConfig([
            'hosts' => [ELASTICSEARCH_HOST],
            'basicAuthentication' => [ELASTICSEARCH_USER, ELASTICSEARCH_PASSWORD],
        ]);
    }

    public static function getElasticsearchVersion(): string
    {
        if (self::$esVersion) {
            return self::$esVersion;
        }

        return self::$esVersion = self::getClient()->info()['version']['number'];
    }

    public static function minimalElasticsearchVersion(string $version): bool
    {
        return version_compare(self::getElasticsearchVersion(), $version, '>=');
    }

    public function createIndex(ElasticsearchIndexConfigurationInterface $configuration): ElasticsearchIndex
    {
        return new ElasticsearchIndex(self::getClient(), new ElasticsearchMapper($configuration));
    }
}
