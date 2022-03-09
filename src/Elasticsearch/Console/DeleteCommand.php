<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class DeleteCommand
 */
class DeleteCommand extends AbstractCommand
{
    protected static $defaultName = 'elasticsearch:delete';

    /**
     * {@inheritdoc}
     */
    protected function configure()
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
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new SymfonyStyle($input, $output);
        $client = $this->getClient();

        $indices = $input->getArgument('indices');

        if ($input->getOption('all')) {
            $indices = array_keys($client->indices()->getMapping());
        }

        if (!$indices) {
            $style->error('Aucun index à supprimer');

            return 1;
        }

        if ($style->confirm('Confirmez-vous la suppression des index : ' . implode(', ', $indices) . ' ?')) {
            foreach ($client->indices()->getAliases() as $index => $alias) {
                foreach ($indices as $k => $toDelete) {
                    if (isset($alias['aliases'][$toDelete])) {
                        unset($indices[$k]);
                        $client->indices()->deleteAlias(['index' => $index, 'name' => $toDelete]);
                    }
                }
            }

            if (!empty($indices)) {
                foreach (array_chunk($indices, 10) as $chunk) {
                    $client->indices()->delete(['index' => implode(',', $chunk)]);
                }
            }
        }

        return 0;
    }
}
