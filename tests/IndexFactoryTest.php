<?php

namespace Bdf\Prime\Indexer;

use Bdf\Prime\Entity\Instantiator\Instantiator;
use Bdf\Prime\Indexer\Elasticsearch\ElasticsearchIndex;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchIndexConfigurationInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapper;
use Bdf\Prime\Indexer\Exception\IndexNotFoundException;
use Bdf\Prime\Indexer\Exception\InvalidIndexConfigurationException;
use Bdf\Prime\Indexer\Resolver\MappingResolver;
use Elasticsearch\ClientBuilder;
use ElasticsearchTestFiles\User;
use ElasticsearchTestFiles\UserIndex;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Class IndexFactoryTest
 */
class IndexFactoryTest extends TestCase
{
    /**
     * @var IndexFactory
     */
    private $factory;

    /**
     * @var MappingResolver
     */
    private $resolver;

    protected function setUp(): void
    {
        $this->factory = new IndexFactory(
            [
                ElasticsearchIndexConfigurationInterface::class => function (ElasticsearchIndexConfigurationInterface $configuration) {
                    return new ElasticsearchIndex(
                        ClientBuilder::fromConfig(['hosts' => [$_ENV['ELASTICSEARCH_HOST']]]),
                        new ElasticsearchMapper($configuration, new Instantiator())
                    );
                },
            ],
            $this->resolver = new MappingResolver($this->createMock(ContainerInterface::class), [
                User::class => new UserIndex()
            ])
        );
    }

    /**
     *
     */
    public function test_for()
    {
        $this->assertInstanceOf(ElasticsearchIndex::class, $this->factory->for(User::class));
        $this->assertEquals(new UserIndex(), $this->factory->for(User::class)->config());
        $this->assertSame($this->factory->for(User::class), $this->factory->for(User::class));
    }

    /**
     *
     */
    public function test_for_invalid_config()
    {
        $this->expectException(InvalidIndexConfigurationException::class);

        $this->resolver->register($this->createMock(IndexConfigurationInterface::class), 'invalid');
        $this->factory->for('invalid');
    }

    /**
     *
     */
    public function test_for_config_not_found()
    {
        $this->expectException(IndexNotFoundException::class);
        $this->expectExceptionMessage('The index for entity "not_found" cannot be found');

        $this->factory->for('not_found');
    }
}
