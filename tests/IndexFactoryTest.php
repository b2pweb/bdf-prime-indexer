<?php

namespace Bdf\Prime\Indexer;

use Bdf\Prime\Entity\Instantiator\Instantiator;
use Bdf\Prime\Indexer\Elasticsearch\ElasticsearchIndex;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchIndexConfigurationInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapper;
use Bdf\Prime\Indexer\Exception\IndexNotFoundException;
use Bdf\Prime\Indexer\Resolver\MappingResolver;
use ElasticsearchTestFiles\User;
use ElasticsearchTestFiles\UserIndex;
use Psr\Container\ContainerInterface;

/**
 * Class IndexFactoryTest
 */
class IndexFactoryTest extends IndexTestCase
{
    /**
     * @var IndexFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $resolver = new MappingResolver(
            $this->createMock(ContainerInterface::class),
            [
                User::class => new UserIndex()
            ]
        );
        $this->factory = new IndexFactory(
            [
                ElasticsearchIndexConfigurationInterface::class => function (ElasticsearchIndexConfigurationInterface $configuration) {
                    return new ElasticsearchIndex(
                        self::getClient(),
                        new ElasticsearchMapper($configuration, new Instantiator())
                    );
                },
            ],
            $resolver
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
    public function test_legacy_constructor()
    {
        $this->factory = new IndexFactory(
            [
                ElasticsearchIndexConfigurationInterface::class => function (ElasticsearchIndexConfigurationInterface $configuration) {
                    return new ElasticsearchIndex(
                        self::getClient(),
                        new ElasticsearchMapper($configuration, new Instantiator())
                    );
                },
            ],
            [
                User::class => new UserIndex()
            ]
        );

        $this->assertInstanceOf(ElasticsearchIndex::class, $this->factory->for(User::class));
        $this->assertEquals(new UserIndex(), $this->factory->for(User::class)->config());
        $this->assertSame($this->factory->for(User::class), $this->factory->for(User::class));
    }

    /**
     *
     */
    public function test_for_invalid_config()
    {
        $this->expectException(\LogicException::class);

        $this->factory->register('invalid', $this->createMock(IndexConfigurationInterface::class));
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
