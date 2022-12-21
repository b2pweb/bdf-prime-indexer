<?php

namespace Bdf\Prime\Indexer\Bundle\Factory;

use Bdf\Prime\Indexer\Denormalize\DenormalizedIndex;
use Bdf\Prime\Indexer\Denormalize\DenormalizerInterface;
use Bdf\Prime\Indexer\IndexFactory;
use Bdf\Prime\Indexer\IndexInterface;

/**
 * Index factory for denormalized index
 *
 * @implements IndexFactoryInterface<DenormalizerInterface>
 */
final class DenormalizerIndexFactory implements IndexFactoryInterface
{
    /**
     * {@inheritdoc}
     *
     * @param DenormalizerInterface $config
     */
    public function __invoke($config, IndexFactory $factory = null): IndexInterface
    {
        if (!$factory) {
            throw new \InvalidArgumentException('$factory is mandatory');
        }

        return new DenormalizedIndex($config, $factory->for($config->denormalizedClass()));
    }

    /**
     * {@inheritdoc}
     */
    public static function type(): string
    {
        return DenormalizerInterface::class;
    }
}
