<?php

namespace Elasticsearch\Mapper\Property\Accessor;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\SimplePropertyAccessor;
use PHPUnit\Framework\TestCase;

/**
 * Class SimplePropertyAccessorTest
 */
class SimplePropertyAccessorTest extends TestCase
{
    /**
     *
     */
    public function test_readFromModel()
    {
        $user = new \User();
        $user->setRoles(['3', '7']);

        $accessor = new SimplePropertyAccessor('roles');
        $this->assertEquals(['3', '7'], $accessor->readFromModel($user));
    }

    /**
     *
     */
    public function test_getter_not_found()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot find getter for property not_found on entity User');

        $user = new \User();

        $accessor = new SimplePropertyAccessor('not_found');
        $accessor->readFromModel($user);
    }

    /**
     *
     */
    public function test_writeToModel()
    {
        $user = new \User();

        $accessor = new SimplePropertyAccessor('roles');
        $accessor->writeToModel($user, ['3', '7']);

        $this->assertEquals(['3', '7'], $user->roles());
    }
}
