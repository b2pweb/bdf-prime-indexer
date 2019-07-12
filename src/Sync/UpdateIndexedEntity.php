<?php

namespace Bdf\Prime\Indexer\Sync;

use Bdf\Bus\Handler\Message\SelfHandler;
use Bdf\Bus\Queue\Message\Queueable;
use Bdf\Prime\Indexer\IndexFactory;

/**
 * Update an entity store into the index
 */
final class UpdateIndexedEntity implements SelfHandler, Queueable
{
    /**
     * @var string
     */
    private $index;

    /**
     * @var object
     */
    private $entity;


    /**
     * UpdateIndexedEntity constructor.
     *
     * @param string $index The index name
     * @param object $entity The entity to update
     */
    public function __construct(string $index, $entity)
    {
        $this->index = $index;
        $this->entity = $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(IndexFactory $factory)
    {
        $index = $factory->for($this->index);

        if ($index->contains($this->entity)) {
            $index->update($this->entity);
        } else {
            $index->add($this->entity);
        }
    }
}
