<?php

namespace ElasticsearchTestFiles;

class ContainerEntity
{
    public ?string $id = null;
    public ?string $name = null;
    public ?EmbeddedEntity $foo = null;
    public ?EmbeddedEntity $bar = null;
    public array $baz = [];

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

    /**
     * @return array
     */
    public function baz(): array
    {
        return $this->baz;
    }

    /**
     * @param array $baz
     *
     * @return ContainerEntity
     */
    public function setBaz(array $baz): ContainerEntity
    {
        $this->baz = $baz;

        return $this;
    }
}
