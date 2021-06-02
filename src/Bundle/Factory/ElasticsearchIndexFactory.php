<?php

namespace Bdf\Prime\Indexer\Bundle\Factory;

use Bdf\Prime\Entity\Instantiator\Instantiator;
use Bdf\Prime\Entity\Instantiator\InstantiatorInterface;
use Bdf\Prime\Indexer\Elasticsearch\ElasticsearchIndex;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchIndexConfigurationInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapper;
use Bdf\Prime\Indexer\IndexFactory;
use Bdf\Prime\Indexer\IndexInterface;
use Elasticsearch\Client;
use Psr\Container\ContainerInterface;

/**
 * Index factory for elasticsearch
 *
 * @implements IndexFactoryInterface<ElasticsearchIndexConfigurationInterface>
 */
final class ElasticsearchIndexFactory implements IndexFactoryInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * ElasticsearchIndexFactory constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public static function type(): string
    {
        return ElasticsearchIndexConfigurationInterface::class;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke($config, IndexFactory $factory): IndexInterface
    {
        return new ElasticsearchIndex(
            $this->container->get(Client::class),
            new ElasticsearchMapper(
                $config,
                new Instantiator()
                //$this->container->get(InstantiatorInterface::class) @todo not public
            )
        );
    }
}
