<?php

namespace Bdf\Prime\Indexer\Sync;

use Bdf\Prime\Indexer\IndexFactory;

/**
 * Add the entity to the index
 */
final class AddToIndex
{
    /**
     * @var class-string
     */
    private string $index;

    /**
     * @var object
     */
    private object $entity;


    /**
     * AddToIndex constructor.
     *
     * @param class-string $index The index name
     * @param object $entity The entity to index
     */
    public function __construct(string $index, object $entity)
    {
        $this->index = $index;
        $this->entity = $entity;
    }

    /**
     * @return class-string
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
        $factory->for($this->index)->add($this->entity);
    }
}
