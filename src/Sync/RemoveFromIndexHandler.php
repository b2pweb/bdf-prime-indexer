<?php

namespace Bdf\Prime\Indexer\Sync;

use Bdf\Prime\Indexer\IndexFactory;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

/**
 * Handle @see RemoveFromIndex
 */
#[AsMessageHandler]
final class RemoveFromIndexHandler implements MessageHandlerInterface
{
    /**
     * @var IndexFactory
     */
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

    public function __invoke(RemoveFromIndex $message): void
    {
        $this->factory->for($message->index())->remove($message->entity());
    }
}
