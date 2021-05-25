<?php

namespace Bdf\Prime\Indexer\Sync;

use Bdf\Prime\Indexer\IndexFactory;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

/**
 * Handle @see UpdateIndexedEntity
 */
final class UpdateIndexedEntityHandler implements MessageHandlerInterface
{
    /**
     * @var IndexFactory
     */
    private $factory;

    /**
     * AddToIndexHandler constructor.
     *
     * @param IndexFactory $factory
     */
    public function __construct(IndexFactory $factory)
    {
        $this->factory = $factory;
    }

    public function __invoke(UpdateIndexedEntity $message): void
    {
        $index = $this->factory->for($message->index());
        $entity = $message->entity();

        if ($index->contains($entity)) {
            $index->update($entity);
        } else {
            $index->add($entity);
        }
    }
}
