<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class DeleteCommand
 */
class DeleteCommand extends AbstractCommand
{
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
    public static function names()
    {
        return ['elasticsearch:delete'];
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute()
    {
        $client = $this->getClient();

        $indices = $this->argument('indices');

        if ($this->option('all')) {
            $indices = array_keys($client->indices()->getMapping());
        }

        if (!$indices) {
            $this->error('Aucun index à supprimer');

            return;
        }

        if ($this->confirm('Confirmez-vous la suppression des index : ' . implode(', ', $indices) . ' ?')) {
            $client->indices()->delete([
                'index' => implode(',', $indices)
            ]);
        }
    }
}
