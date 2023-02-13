<?php

namespace Bdf\Prime\Indexer\Bundle;

use Bdf\Prime\Indexer\Bundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    public function test_simple_config()
    {
        $config = [
            'elasticsearch' => [
                'hosts' => ['localhost:9200'],
            ],
        ];

        $configuration = new Configuration();
        $treeBuilder = $configuration->getConfigTreeBuilder();

        $this->assertEquals([
            'elasticsearch' => [
                'hosts' => ['localhost:9200'],
            ],
            'indexes' => [],
        ], (new Processor())->process($treeBuilder->buildTree(), [$config]));
    }

    public function test_with_basic_auth()
    {
        $config = [
            'elasticsearch' => [
                'hosts' => ['localhost:9200'],
                'basicAuthentication' => ['username', 'password'],
            ],
        ];

        $configuration = new Configuration();
        $treeBuilder = $configuration->getConfigTreeBuilder();

        $this->assertEquals([
            'elasticsearch' => [
                'hosts' => ['localhost:9200'],
                'basicAuthentication' => ['username', 'password'],
            ],
            'indexes' => [],
        ], (new Processor())->process($treeBuilder->buildTree(), [$config]));
    }

    public function test_with_empty_basic_auth()
    {
        $config = [
            'elasticsearch' => [
                'hosts' => ['localhost:9200'],
                'basicAuthentication' => ['', ''],
            ],
        ];

        $configuration = new Configuration();
        $treeBuilder = $configuration->getConfigTreeBuilder();

        $this->assertEquals([
            'elasticsearch' => [
                'hosts' => ['localhost:9200'],
            ],
            'indexes' => [],
        ], (new Processor())->process($treeBuilder->buildTree(), [$config]));
    }

    public function test_all_options()
    {
        $config = [
            'elasticsearch' => [
                'hosts' => ['localhost:9200'],
                'connectionParams' => ['foo' => 'bar'],
                'retries' => 5,
                'sslCert' => 'bar',
                'sslKey' => 'rab',
                'sslVerification' => false,
                'sniffOnStart' => true,
                'basicAuthentication' => ['username', 'password'],
            ],
        ];

        $configuration = new Configuration();
        $treeBuilder = $configuration->getConfigTreeBuilder();

        $this->assertEquals([
            'elasticsearch' => [
                'hosts' => ['localhost:9200'],
                'connectionParams' => ['foo' => 'bar'],
                'retries' => 5,
                'sslCert' => 'bar',
                'sslKey' => 'rab',
                'sslVerification' => false,
                'sniffOnStart' => true,
                'basicAuthentication' => ['username', 'password'],
            ],
            'indexes' => [],
        ], (new Processor())->process($treeBuilder->buildTree(), [$config]));
    }
}
