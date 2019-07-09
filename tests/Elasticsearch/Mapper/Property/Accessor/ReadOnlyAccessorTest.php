<?php

namespace Elasticsearch\Mapper\Property\Accessor;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\ReadOnlyAccessor;
use PHPUnit\Framework\TestCase;

/**
 * Class ReadOnlyAccessorTest
 */
class ReadOnlyAccessorTest extends TestCase
{
    /**
     *
     */
    public function test_readFromModel()
    {
        $user = new \User();
        $user->setRoles(['3', '7']);

        $accessor = new ReadOnlyAccessor('roles');
        $this->assertEquals(['3', '7'], $accessor->readFromModel($user));
    }

    /**
     *
     */
    public function test_writeToModel()
    {
        $user = new \User();

        $accessor = new ReadOnlyAccessor('roles');
        $accessor->writeToModel($user, ['3', '7']);

        $this->assertEquals([], $user->roles());
    }
}
