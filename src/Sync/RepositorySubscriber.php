<?php

namespace Bdf\Prime\Indexer\Sync;

use Bdf\Bus\MessageDispatcherInterface;
use Bdf\Prime\Indexer\ShouldBeIndexedConfigurationInterface;
use Bdf\Prime\Repository\EntityRepository;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Subscribe to repository events for synchronize the related index
 */
final class RepositorySubscriber
{
    /**
     * @var MessageDispatcherInterface|MessageBusInterface
     * @psalm-var MessageBusInterface
     */
    private $dispatcher;

    /**
     * @var string
     */
    private $index;

    /**
     * @var object
     */
    private $config;


    /**
     * RepositorySubscriber constructor.
     *
     * @param MessageDispatcherInterface|MessageBusInterface $dispatcher The message bus for perform index operations
     * @psalm-param MessageBusInterface $dispatcher
     * @param string $index The index name
     * @param object $config The index configuration
     */
    public function __construct($dispatcher, string $index, $config)
    {
        $this->dispatcher = $dispatcher;
        $this->index = $index;
        $this->config = $config;
    }

    /**
     * An entity is inserted
     * Index only if it should be indexed
     *
     * @param object $entity
     */
    public function inserted($entity): void
    {
        if ($this->shouldBeIndexed($entity)) {
            $this->dispatcher->dispatch(new AddToIndex($this->index, $entity));
        }
    }

    /**
     * An entity is updated
     *
     * If the entity should still be indexed, it will be updated
     * Otherwise, the entity is removed
     *
     * @param object $entity
     */
    public function updated($entity): void
    {
        if ($this->shouldBeIndexed($entity)) {
            $this->dispatcher->dispatch(new UpdateIndexedEntity($this->index, $entity));
        } else {
            $this->dispatcher->dispatch(new RemoveFromIndex($this->index, $entity));
        }
    }

    /**
     * An entity is deleted from the database
     * The entity will also be removed from index
     *
     * @param object $entity
     */
    public function deleted($entity): void
    {
        $this->dispatcher->dispatch(new RemoveFromIndex($this->index, $entity));
    }

    /**
     * Subscribe to repository writes for synchronize index
     *
     * @param EntityRepository $repository
     */
    public function subscribe(EntityRepository $repository): void
    {
        $repository->inserted([$this, 'inserted']);
        $repository->updated([$this, 'updated']);
        $repository->deleted([$this, 'deleted']);
    }

    /**
     * Check if the entity should be indexed or not
     *
     * @param object $entity Entity to check
     *
     * @return bool
     */
    private function shouldBeIndexed($entity): bool
    {
        return !$this->config instanceof ShouldBeIndexedConfigurationInterface || $this->config->shouldBeIndexed($entity);
    }
}
