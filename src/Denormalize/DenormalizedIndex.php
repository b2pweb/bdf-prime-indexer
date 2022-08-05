<?php

namespace Bdf\Prime\Indexer\Denormalize;

use Bdf\Collection\Stream\Streams;
use Bdf\Prime\Indexer\IndexInterface;
use Bdf\Prime\Indexer\QueryInterface;

/**
 * Index adapter for handle denormalizer
 */
final class DenormalizedIndex implements IndexInterface
{
    private DenormalizerInterface $denormalizer;
    private IndexInterface $index;

    /**
     * DenormalizedIndex constructor.
     *
     * @param DenormalizerInterface $denormalizer
     * @param IndexInterface $index
     */
    public function __construct(DenormalizerInterface $denormalizer, IndexInterface $index)
    {
        $this->denormalizer = $denormalizer;
        $this->index = $index;
    }

    /**
     * {@inheritdoc}
     */
    public function config()
    {
        return $this->denormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function add($entity): void
    {
        $this->index->add($this->denormalizer->denormalize($entity));
    }

    /**
     * {@inheritdoc}
     */
    public function contains($entity): bool
    {
        return $this->index->contains($this->denormalizer->denormalize($entity));
    }

    /**
     * {@inheritdoc}
     */
    public function remove($entity): void
    {
        $this->index->remove($this->denormalizer->denormalize($entity));
    }

    /**
     * {@inheritdoc}
     */
    public function update($entity, ?array $attributes = null): void
    {
        $this->index->update($this->denormalizer->denormalize($entity), $attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function query(bool $withDefaultScope = true): QueryInterface
    {
        return $this->index->query($withDefaultScope);
    }

    /**
     * {@inheritdoc}
     */
    public function create(iterable $entities = [], array $options = []): void
    {
        $this->index->create(
            Streams::wrap($entities)->map([$this->denormalizer, 'denormalize']),
            $options
        );
    }

    /**
     * {@inheritdoc}
     */
    public function drop(): void
    {
        $this->index->drop();
    }

    /**
     * {@inheritdoc}
     */
    public function __call(string $name, array $arguments): QueryInterface
    {
        return $this->index->$name(...$arguments);
    }
}
