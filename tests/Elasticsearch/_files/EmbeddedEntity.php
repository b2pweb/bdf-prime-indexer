<?php

namespace ElasticsearchTestFiles;

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
