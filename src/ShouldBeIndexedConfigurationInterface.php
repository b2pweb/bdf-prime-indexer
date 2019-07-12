<?php

namespace Bdf\Prime\Indexer;

/**
 * Define if an entity should be indexed or not
 */
interface ShouldBeIndexedConfigurationInterface
{
    /**
     * Check if the entity should be indexed
     *
     * This method is called on creation and update :
     * - On creation, the entity is indexed only if returns true
     * - On update, if the method returns false, the entity will be removed, else, it will be updated
     * - The check is not performed on deletion
     *
     * Note: This method can be defined independently of CustomEntitiesConfigurationInterface::entities()
     *       So, if entities() returns entities which should not be indexed, there were not indexed
     *
     * @param object $entity The entity to check
     *
     * @return bool True if it should be indexed
     */
    public function shouldBeIndexed($entity): bool;
}
