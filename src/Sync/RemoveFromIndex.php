<?php

namespace Bdf\Prime\Indexer\Sync;

use Bdf\Prime\Indexer\IndexFactory;

/**
 * Remove an entity from the index
 */
final class RemoveFromIndex
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
     * RemoveFromIndex constructor.
     *
     * @param string $index The index name
     * @param object $entity The entity to remove
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
    public function handle(IndexFactory $factory)
    {
        $factory->for($this->index)->remove($this->entity);
    }
}
