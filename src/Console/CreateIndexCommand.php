<?php

namespace Bdf\Prime\Indexer\Console;

use Bdf\Collection\Stream\Streams;
use Bdf\Prime\Indexer\CustomEntitiesConfigurationInterface;
use Bdf\Prime\Indexer\IndexFactory;
use Bdf\Prime\Indexer\ShouldBeIndexedConfigurationInterface;
use Bdf\Prime\Query\Contract\Paginable;
use Bdf\Prime\Repository\EntityRepository;
use Bdf\Prime\ServiceLocator;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Create the index for the given entity
 */
#[AsCommand('prime:indexer:create', 'Create the index for the given entity')]
class CreateIndexCommand extends Command
{
    protected static $defaultName = 'prime:indexer:create';

    private IndexFactory $indexes;
    private ServiceLocator $prime;
    private ?LoggerInterface $logger;
    private ?ProgressBar $progressBar = null;


    /**
     * CreateIndexCommand constructor.
     *
     * @param IndexFactory $indexes
     * @param ServiceLocator $prime
     * @param LoggerInterface|null $logger
     */
    public function __construct(IndexFactory $indexes, ServiceLocator $prime, ?LoggerInterface $logger = null)
    {
        parent::__construct();

        $this->indexes = $indexes;
        $this->prime = $prime;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
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
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $index = $this->indexes->for($input->getArgument('entity'));
        $options = json_decode($input->getOption('options'), true);

        if (!is_array($options)) {
            $io->error('Invalid options given');
            return 0;
        }

        $entities = $this->entities($index->config(), $input, $io);

        if (!$input->getOption('no-progress')) {
            $entities = $this->configureProgressBar($entities, $io);
        }

        $index->create(
            $this->filterNotIndexableEntities($index->config(), $entities),
            ['logger' => $this->logger ?? new NullLogger()] + $options
        );

        $this->finishProgressBar($io);

        return 0;
    }

    /**
     * Get entities
     *
     * @param object $config
     * @param InputInterface $input
     * @param StyleInterface $io
     *
     * @return iterable
     *
     * @throws \Bdf\Prime\Exception\PrimeException
     */
    private function entities(object $config, InputInterface $input, StyleInterface $io): iterable
    {
        if ($config instanceof CustomEntitiesConfigurationInterface) {
            return $config->entities();
        }

        /** @var EntityRepository|null $repository */
        $repository = $this->prime->repository($input->getArgument('entity'));

        if ($repository === null) {
            $io->error('Cannot load entities');

            return [];
        }

        $query = $repository->queries()->keyValue();

        if (!$query instanceof Paginable) {
            $query = $repository->queries()->builder();
        }

        return $query->walk();
    }

    /**
     * Configure the progress bar
     *
     * @param iterable $entities
     * @param OutputStyle $io
     *
     * @return iterable
     */
    private function configureProgressBar(iterable $entities, OutputStyle $io): iterable
    {
        if ($entities instanceof \Traversable && method_exists($entities, 'size')) {
            $size = $entities->size();
        } elseif (is_array($entities) || $entities instanceof \Countable) {
            $size = count($entities);
        } else {
            return $entities;
        }

        $this->progressBar = $io->createProgressBar($size);
        return Streams::wrap($entities)->map(function (object $entity) {
            /** @psalm-suppress PossiblyNullReference */
            $this->progressBar->advance();

            return $entity;
        });
    }

    /**
     * Finish the progressBar
     *
     * @param StyleInterface $io
     */
    private function finishProgressBar(StyleInterface $io): void
    {
        if ($this->progressBar) {
            $this->progressBar->finish();
            $io->newLine();
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
    private function filterNotIndexableEntities(object $config, iterable $entities): iterable
    {
        if (!$config instanceof ShouldBeIndexedConfigurationInterface) {
            return $entities;
        }

        return Streams::wrap($entities)->filter([$config, 'shouldBeIndexed']);
    }
}
