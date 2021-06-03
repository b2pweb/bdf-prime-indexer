<?php

namespace Bdf\Prime\Indexer\Resolver;

use Bdf\Prime\Indexer\TestKernel;
use ElasticsearchTestFiles\User;
use ElasticsearchTestFiles\UserIndex;
use PHPUnit\Framework\TestCase;

/**
 * Class MappingResolverTest
 */
class MappingResolverTest extends TestCase
{
    /**
     * @var MappingResolver
     */
    private $resolver;

    protected function setUp(): void
    {
        $app = new TestKernel('dev', false);
        $app->boot();

        $this->resolver = new MappingResolver($app->getContainer());
    }

    /**
     *
     */
    public function test_resolve_not_found_should_return_null()
    {
        $this->assertNull($this->resolver->resolve('not_found'));
    }

    /**
     *
     */
    public function test_register_and_resolve_with_instance()
    {
        $this->resolver->register($index = new UserIndex());

        $this->assertSame($index, $this->resolver->resolve(User::class));
    }

    /**
     *
     */
    public function test_register_and_resolve_with_class_name()
    {
        $this->resolver->register(UserIndex::class, User::class);

        $this->assertInstanceOf(UserIndex::class, $this->resolver->resolve(User::class));
    }

    /**
     *
     */
    public function test_register_and_resolve_with_class_name_missing_entity_class()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$entityClassName is required when passing a string as first parameter');

        $this->resolver->register(UserIndex::class);
    }
}
