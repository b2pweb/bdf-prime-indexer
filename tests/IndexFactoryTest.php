<?php

namespace Bdf\Prime\Indexer;

use Bdf\Prime\Entity\Instantiator\Instantiator;
use Bdf\Prime\Indexer\Elasticsearch\ElasticsearchIndex;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchIndexConfigurationInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchMapper;

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
                \User::class => new \UserIndex()
            ]
        );
    }

    /**
     *
     */
    public function test_for()
    {
        $this->assertInstanceOf(ElasticsearchIndex::class, $this->factory->for(\User::class));
        $this->assertEquals(new \UserIndex(), $this->factory->for(\User::class)->config());
        $this->assertSame($this->factory->for(\User::class), $this->factory->for(\User::class));
    }

    /**
     *
     */
    public function test_for_invalid_config()
    {
        $this->expectException(\LogicException::class);

        $this->factory->register('invalid', new \stdClass());
        $this->factory->for('invalid');
    }
}
