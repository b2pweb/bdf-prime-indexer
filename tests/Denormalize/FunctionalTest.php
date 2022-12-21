<?php

namespace Bdf\Prime\Indexer\Denormalize;

use Bdf\Prime\Indexer\Test\TestingIndexer;
use Bdf\Prime\Indexer\TestKernel;
use Bdf\Prime\Prime;
use Bdf\Prime\Test\TestPack;
use DenormalizeTestFiles\IndexedUserAttributes;
use DenormalizeTestFiles\UserAttributes;
use PHPUnit\Framework\TestCase;

class FunctionalTest extends TestCase
{
    /**
     * @var TestingIndexer
     */
    private $indexer;

    /**
     * @var TestPack
     */
    private $testPack;

    /**
     * @var DenormalizedIndex
     */
    private $index;

    /**
     *
     */
    protected function setUp(): void
    {
        $app = new TestKernel('dev', false);
        $app->boot();
        Prime::configure($app->getContainer()->get('prime'));
        $this->indexer = new TestingIndexer($app->getContainer());

        $this->testPack = TestPack::pack();
        $this->testPack->declareEntity(UserAttributes::class)->initialize();

        $this->indexer->index(IndexedUserAttributes::class); // declare real index
        $this->index = $this->indexer->index(UserAttributes::class);
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->indexer->destroy();
        $this->testPack->destroy();
    }

    /**
     *
     */
    public function test_crud()
    {
        $attr = new UserAttributes([
            'userId' => 5,
            'attributes' => [
                'foo' => 'bar',
                'tags' => ['aaa', 'bbb'],
            ]
        ]);
        $attr->insert();

        $this->assertFalse($this->index->contains($attr));
        $this->index->add($attr);
        $this->assertTrue($this->index->contains($attr));

        $this->indexer->flush();

        $indexed = $this->index->query()->where('userId', 5)->first()->get();

        $this->assertEquals(new IndexedUserAttributes([
            'userId' => 5,
            'attributes' => [
                'foo' => 'bar',
                'tags' => ['aaa', 'bbb'],
            ],
            'keys' => ['foo', 'tags'],
            'values' => ['bar', 'aaa', 'bbb'],
            'tags' => ['aaa', 'bbb'],
        ]), $indexed);

        $this->assertEquals([$indexed], $this->index->byTag('bbb')->all());

        $attr->attributes['foo'] = 'rab';
        $this->index->update($attr);
        $this->indexer->flush();

        $indexed = $this->index->query()->where('userId', 5)->first()->get();

        $this->assertEquals(new IndexedUserAttributes([
            'userId' => 5,
            'attributes' => [
                'foo' => 'rab',
                'tags' => ['aaa', 'bbb'],
            ],
            'keys' => ['foo', 'tags'],
            'values' => ['rab', 'aaa', 'bbb'],
            'tags' => ['aaa', 'bbb'],
        ]), $indexed);

        $this->index->remove($attr);
        $this->assertFalse($this->index->contains($attr));
    }
}
