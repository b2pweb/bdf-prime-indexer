<?php

namespace Bdf\Prime\Indexer\Sync;

use Bdf\Prime\Indexer\IndexFactory;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

/**
 * Handle @see AddToIndex
 */
final class AddToIndexHandler implements MessageHandlerInterface
{
    private IndexFactory $factory;

    /**
     * AddToIndexHandler constructor.
     *
     * @param IndexFactory $factory
     */
    public function __construct(IndexFactory $factory)
    {
        $this->factory = $factory;
    }

    public function __invoke(AddToIndex $message): void
    {
        $this->factory->for($message->index())->add($message->entity());
    }
}
