<?php

namespace Bdf\Prime\Indexer\Bundle\Factory;

use Bdf\Prime\Entity\Instantiator\Instantiator;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\ClientInterface;
use Bdf\Prime\Indexer\Elasticsearch\ElasticsearchIndex;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchIndexConfigurationInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapper;
use Bdf\Prime\Indexer\IndexFactory;
use Bdf\Prime\Indexer\IndexInterface;
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
    public function __invoke($config, IndexFactory $factory = null): IndexInterface
    {
        return new ElasticsearchIndex(
            $this->container->get(ClientInterface::class),
            new ElasticsearchMapper(
                $config,
                new Instantiator()
                //$this->container->get(InstantiatorInterface::class) @todo not public
            )
        );
    }
}
