<?php

use Bdf\Prime\Indexer\Elasticsearch\Mapper\ElasticsearchIndexConfigurationInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\PropertyAccessorInterface;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\Accessor\SimplePropertyAccessor;
use Bdf\Prime\Indexer\Elasticsearch\Mapper\Property\PropertiesBuilder;

class ContainerEntity
{
    public ?string $id = null;
    public ?string $name = null;
    public ?EmbeddedEntity $foo = null;
    public ?EmbeddedEntity $bar = null;

    public function id(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): ContainerEntity
    {
        $this->id = $id;

        return $this;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): ContainerEntity
    {
        $this->name = $name;

        return $this;
    }

    public function foo(): ?EmbeddedEntity
    {
        return $this->foo;
    }

    public function setFoo(?EmbeddedEntity $foo): ContainerEntity
    {
        $this->foo = $foo;

        return $this;
    }

    public function bar(): ?EmbeddedEntity
    {
        return $this->bar;
    }

    public function setBar(?EmbeddedEntity $bar): ContainerEntity
    {
        $this->bar = $bar;

        return $this;
    }
}

class EmbeddedEntity
{
    public ?string $key = null;
    public ?int $value = null;

    public function key(): ?string
    {
        return $this->key;
    }

    public function setKey(?string $key): EmbeddedEntity
    {
        $this->key = $key;

        return $this;
    }

    public function value(): ?int
    {
        return $this->value;
    }

    public function setValue(?int $value): EmbeddedEntity
    {
        $this->value = $value;

        return $this;
    }
}

class ContainerEntityIndex implements ElasticsearchIndexConfigurationInterface
{
    /**
     * @inheritDoc
     */
    public function index(): string
    {
        return 'containers';
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return ContainerEntity::class;
    }

    /**
     * @inheritDoc
     */
    public function id(): ?PropertyAccessorInterface
    {
        return new SimplePropertyAccessor('id');
    }

    /**
     * @inheritDoc
     */
    public function properties(PropertiesBuilder $builder): void
    {
        $builder
            ->text('name')
            ->object('foo', EmbeddedEntity::class, function (PropertiesBuilder $builder) {
                $builder
                    ->keyword('key')
                    ->integer('value')
                ;
            })
            ->object('bar', EmbeddedEntity::class, function (PropertiesBuilder $builder) {
                $builder
                    ->keyword('key')
                    ->integer('value')
                ;
            })
        ;
    }

    /**
     * @inheritDoc
     */
    public function analyzers(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function scopes(): array
    {
        return [];
    }
}
