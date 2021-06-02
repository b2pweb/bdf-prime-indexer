<?php

namespace DenormalizeTestFiles;

use Bdf\Prime\Indexer\Denormalize\DenormalizerInterface;

class UserAttributesDenormalizer implements DenormalizerInterface
{
    /**
     * @param UserAttributes $entity
     */
    public function denormalize($entity)
    {
        return new IndexedUserAttributes([
            'userId' => $entity->userId,
            'attributes' => $entity->attributes,
            'keys' => array_keys($entity->attributes),
            'values' => $this->flatten($entity->attributes),
            'tags' => $entity->attributes['tags'] ?? [],
        ]);
    }

    /**
     * @inheritDoc
     */
    public function denormalizedClass(): string
    {
        return IndexedUserAttributes::class;
    }

    /**
     * @inheritDoc
     */
    public function entity(): string
    {
        return UserAttributes::class;
    }

    private function flatten(array $values): array
    {
        $flat = [];

        foreach ($values as $value) {
            if (is_scalar($value)) {
                $flat[] = $value;
            } elseif (is_array($value)) {
                $flat = array_merge($flat, $this->flatten($value));
            }
        }

        return $flat;
    }
}
