<?php

namespace Bdf\Prime\Indexer\Sync;

use Bdf\Bus\Handler\Message\SelfHandler;
use Bdf\Bus\Queue\Message\Queueable;
use Bdf\Prime\Indexer\IndexFactory;

/**
 * Add the entity to the index
 */
final class AddToIndex implements SelfHandler, Queueable
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
     * AddToIndex constructor.
     *
     * @param string $index The index name
     * @param object $entity The entity to index
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
        $factory->for($this->index)->add($this->entity);
    }
}
