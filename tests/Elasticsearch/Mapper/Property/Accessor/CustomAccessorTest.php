<?php

namespace Elasticsearch\Mapper\Property\Accessor;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\CustomAccessor;
use PHPUnit\Framework\TestCase;

/**
 * Class CustomAccessorTest
 */
class CustomAccessorTest extends TestCase
{
    /**
     *
     */
    public function test_readFromModel()
    {
        $user = new \User();

        $accessor = new CustomAccessor(function () use(&$parameters) {
            $parameters = func_get_args();

            return 'foo';
        });

        $this->assertEquals('foo', $accessor->readFromModel($user));
        $this->assertSame($user, $parameters[0]);
        $this->assertNull($parameters[1]);
    }

    /**
     *
     */
    public function test_writeToModel()
    {
        $user = new \User();

        $accessor = new CustomAccessor(function (\User $user) use(&$parameters) {
            $parameters = func_get_args();

            $user->setName('new name');
        });

        $accessor->writeToModel($user, 'foo');

        $this->assertEquals('new name', $user->name());
        $this->assertSame($user, $parameters[0]);
        $this->assertEquals('foo', $parameters[1]);
    }
}
