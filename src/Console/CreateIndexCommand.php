<?php

namespace Bdf\Prime\Indexer\Console;

use Bdf\Collection\Stream\Streams;
use Bdf\Console\Command;
use Bdf\Prime\Indexer\CustomEntitiesConfigurationInterface;
use Bdf\Prime\Indexer\IndexFactory;
use Bdf\Prime\Indexer\IndexInterface;
use Bdf\Prime\Indexer\ShouldBeIndexedConfigurationInterface;
use Bdf\Prime\Query\Contract\Paginable;
use Bdf\Prime\Repository\EntityRepository;
use Bdf\Prime\ServiceLocator;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Create the index for the given entity
 */
class CreateIndexCommand extends Command
{
    /**
     * @var ProgressBar
     */
    private $progressBar;


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Create the index for the given entity')
            ->addArgument('entity', InputArgument::REQUIRED, 'The entity class name')
            ->addOption('no-progress', null, InputOption::VALUE_NONE, 'Disable progress bar on indexing')
            ->addOption('options', 'o', InputOption::VALUE_REQUIRED, 'Create options array, in JSON format', '{}')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public static function names()
    {
        return ['prime:indexer:create'];
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute()
    {
        /** @var IndexInterface $index */
        $index = $this->get(IndexFactory::class)->for($this->argument('entity'));
        $options = json_decode($this->option('options'), true);

        if (!is_array($options)) {
            $this->error('Invalid options given');
            return;
        }

        $entities = $this->entities($index->config());

        if (!$this->option('no-progress')) {
            $entities = $this->configureProgressBar($entities);
        }

        $index->create(
            $this->filterNotIndexableEntities($index->config(), $entities),
            ['logger' => $this->log()] + $options
        );

        $this->finishProgressBar();
    }

    /**
     * Get entities
     *
     * @param object $config
     *
     * @return iterable
     */
    private function entities($config): iterable
    {
        if ($config instanceof CustomEntitiesConfigurationInterface) {
            return $config->entities();
        }

        /** @var EntityRepository $repository */
        $repository = $this->get(ServiceLocator::class)->repository($this->argument('entity'));

        if ($repository === null) {
            $this->alert('Cannot load entities');

            return [];
        }

        $query = $repository->keyValue();

        // Check if paginationCount() exists for compatibility with BDF 1.5
        if (!$query instanceof Paginable || !method_exists($query, 'paginationCount')) {
            $query = $repository->builder();
        }

        return $query->walk();
    }

    /**
     * Configure the progress bar
     *
     * @param iterable $entities
     *
     * @return iterable
     */
    private function configureProgressBar(iterable $entities): iterable
    {
        if (method_exists($entities, 'size')) {
            $size = $entities->size();
        } elseif (is_array($entities) || $entities instanceof \Countable) {
            $size = count($entities);
        } else {
            return $entities;
        }

        $this->progressBar = $this->progressBar($size);
        return Streams::wrap($entities)->map(function ($entity) {
            $this->progressBar->advance();

            return $entity;
        });
    }

    /**
     * Finish the progressBar
     */
    private function finishProgressBar(): void
    {
        if ($this->progressBar) {
            $this->progressBar->finish();
            $this->line('');
        }
    }

    /**
     * Filter entities that should not be indexed
     *
     * Note: Filter must be applied after progress bar to not break the advance
     *
     * @param object $config
     * @param iterable $entities
     *
     * @return iterable
     */
    private function filterNotIndexableEntities($config, iterable $entities): iterable
    {
        if (!$config instanceof ShouldBeIndexedConfigurationInterface) {
            return $entities;
        }

        return Streams::wrap($entities)->filter([$config, 'shouldBeIndexed']);
    }
}
