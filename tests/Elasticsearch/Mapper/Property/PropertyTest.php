<?php

namespace Elasticsearch\Mapper\Property;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\CsvAnalyzer;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Analyzer\StandardAnalyzer;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\SimplePropertyAccessor;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Property;
use PHPUnit\Framework\TestCase;

/**
 * Class PropertyTest
 */
class PropertyTest extends TestCase
{
    /**
     *
     */
    public function test_getters()
    {
        $property = new Property('country', ['index' => 'not_analyzed'], new StandardAnalyzer(), 'string', new SimplePropertyAccessor('country'));

        $this->assertEquals('country', $property->name());
        $this->assertEquals(['index' => 'not_analyzed'], $property->declaration());
        $this->assertEquals('string', $property->type());
        $this->assertEquals(new SimplePropertyAccessor('country'), $property->accessor());
        $this->assertEquals(new StandardAnalyzer(), $property->analyzer());
    }

    /**
     *
     */
    public function test_readFromModel()
    {
        $user = new \User();
        $user->setRoles(['3', '7']);

        $property = new Property('roles', [], new CsvAnalyzer(), 'string', new SimplePropertyAccessor('roles'));
        $this->assertEquals('3,7', $property->readFromModel($user));
    }

    /**
     *
     */
    public function test_writeToModel()
    {
        $user = new \User();

        $property = new Property('roles', [], new CsvAnalyzer(), 'string', new SimplePropertyAccessor('roles'));
        $property->writeToModel($user, '3,7');

        $this->assertEquals(['3', '7'], $user->roles());
    }
}
