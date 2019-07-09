<?php

namespace Elasticsearch\Mapper\Property\Accessor;

use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\EmbeddedAccessor;
use PHPUnit\Framework\TestCase;

/**
 * Class EmbeddedAccessorTest
 */
class EmbeddedAccessorTest extends TestCase
{
    /**
     *
     */
    public function test_readFromModel_embedded_not_set()
    {
        $entity = new MyRootEntity();
        $accessor = new EmbeddedAccessor(['embedded', 'value']);

        $this->assertNull($accessor->readFromModel($entity));
    }

    /**
     *
     */
    public function test_readFromModel_with_className_embedded_not_set()
    {
        $entity = new MyRootEntity();
        $accessor = new EmbeddedAccessor(['embedded' => MyEmbeddedEntity::class, 'value']);

        $this->assertNull($accessor->readFromModel($entity));
    }

    /**
     *
     */
    public function test_readFromModel_success()
    {
        $entity = new MyRootEntity();
        $entity->setEmbedded((new MyEmbeddedEntity())->setValue(42));

        $accessor = new EmbeddedAccessor(['embedded', 'value']);

        $this->assertEquals(42, $accessor->readFromModel($entity));
    }

    /**
     *
     */
    public function test_readFromModel_success_with_className()
    {
        $entity = new MyRootEntity();
        $entity->setEmbedded((new MyEmbeddedEntity())->setValue(42));

        $accessor = new EmbeddedAccessor(['embedded' => MyEmbeddedEntity::class, 'value']);

        $this->assertEquals(42, $accessor->readFromModel($entity));
    }

    /**
     *
     */
    public function test_writeToModel_without_embedded_className_should_do_nothing()
    {
        $entity = new MyRootEntity();
        $accessor = new EmbeddedAccessor(['embedded', 'value']);

        $accessor->writeToModel($entity, 42);

        $this->assertNull($entity->embedded());
    }

    /**
     *
     */
    public function test_writeToModel_without_embedded_with_className_should_instantiate_embedded()
    {
        $entity = new MyRootEntity();
        $accessor = new EmbeddedAccessor(['embedded' => MyEmbeddedEntity::class, 'value']);

        $accessor->writeToModel($entity, 42);

        $this->assertInstanceOf(MyEmbeddedEntity::class, $entity->embedded());
        $this->assertEquals(42, $entity->embedded()->value());
    }

    /**
     *
     */
    public function test_writeToModel_with_embedded_with_className_should_keep_embedded_instance()
    {
        $entity = new MyRootEntity();
        $entity->setEmbedded($embedded = new MyEmbeddedEntity());
        $accessor = new EmbeddedAccessor(['embedded' => MyEmbeddedEntity::class, 'value']);

        $accessor->writeToModel($entity, 42);

        $this->assertSame($embedded, $entity->embedded());
        $this->assertEquals(42, $embedded->value());
    }
}

class MyRootEntity
{
    private $embedded;

    /**
     * @return mixed
     */
    public function embedded()
    {
        return $this->embedded;
    }

    /**
     * @param mixed $embedded
     *
     * @return $this
     */
    public function setEmbedded($embedded)
    {
        $this->embedded = $embedded;

        return $this;
    }
}

class MyEmbeddedEntity
{
    private $value;

    /**
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     *
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }
}
