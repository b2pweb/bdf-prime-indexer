<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Console;

use Bdf\Console\Command;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class AbstractCommand
 */
class AbstractCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->addOption('config', 'c', InputOption::VALUE_OPTIONAL, 'Identifiant de configuration à utiliser')
            ->addOption('hosts', null, InputOption::VALUE_OPTIONAL, 'Liste des noeuds elasticsearch')
        ;
    }

    /**
     * @return Client
     */
    protected function getClient()
    {
        if ($this->option('config') || $this->option('hosts')) {
            return ClientBuilder::fromConfig($this->getClientConfig());
        }

        return $this->di[Client::class];
    }

    /**
     * @return array
     */
    protected function getClientConfig()
    {
        $clientConfig = [
            'hosts' => ['localhost']
        ];

        if ($this->option('config')) {
            $config = $this->di->get('config');

            if ($config->has($this->option('config') . '.hosts')) {
                $clientConfig['hosts'] = $config->get($this->option('config') . '.hosts');
            }
        }

        if ($this->option('hosts')) {
            $clientConfig['hosts'] = explode(',', $this->option('hosts'));
        }

        return $clientConfig;
    }
}
