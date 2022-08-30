<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Console;

use Bdf\Prime\Indexer\Elasticsearch\Adapter\Exception\ElasticsearchExceptionInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class DeleteCommand
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[AsCommand('elasticsearch:delete', 'Supprime un ou plusieurs index')]
class DeleteCommand extends AbstractCommand
{
    protected static $defaultName = 'elasticsearch:delete';

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setDescription('Supprime un ou plusieurs index')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Supprime tous les index')
            ->addArgument('indices', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Liste des index à supprimer')
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ElasticsearchExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $client = $this->getClient();

        $indices = $input->getArgument('indices');

        if ($input->getOption('all')) {
            $indices = $client->getAllIndexes();
        }

        if (!$indices) {
            $style->error('Aucun index à supprimer');

            return 1;
        }

        if ($style->confirm('Confirmez-vous la suppression des index : ' . implode(', ', $indices) . ' ?')) {
            foreach ($client->getAllAliases() as $index => $alias) {
                foreach ($indices as $k => $toDelete) {
                    if ($alias->contains($toDelete)) {
                        unset($indices[$k]);
                        $client->deleteAliases($index, [$toDelete]);
                    }
                }
            }

            if (!empty($indices)) {
                foreach (array_chunk($indices, 10) as $chunk) {
                    $client->deleteIndex(...$chunk);
                }
            }
        }

        return 0;
    }
}
