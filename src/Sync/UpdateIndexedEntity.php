<?php

namespace Bdf\Prime\Indexer\Sync;

use Bdf\Prime\Indexer\IndexFactory;

/**
 * Update an entity store into the index
 */
final class UpdateIndexedEntity
{
    /**
     * @var string
     */
    private string $index;

    /**
     * @var object
     */
    private object $entity;


    /**
     * UpdateIndexedEntity constructor.
     *
     * @param string $index The index name
     * @param object $entity The entity to update
     */
    public function __construct(string $index, object $entity)
    {
        $this->index = $index;
        $this->entity = $entity;
    }

    /**
     * Get the index name
     *
     * @return string
     */
    public function index(): string
    {
        return $this->index;
    }

    /**
     * @return object
     */
    public function entity(): object
    {
        return $this->entity;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(IndexFactory $factory): void
    {
        $index = $factory->for($this->index);

        if ($index->contains($this->entity)) {
            $index->update($this->entity);
        } else {
            $index->add($this->entity);
        }
    }
}
