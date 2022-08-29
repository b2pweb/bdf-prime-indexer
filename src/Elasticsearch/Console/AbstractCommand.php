<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Console;

use Bdf\Prime\Indexer\Elasticsearch\Adapter\ClientFactory;
use Bdf\Prime\Indexer\Elasticsearch\Adapter\ClientInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractCommand
 */
class AbstractCommand extends Command
{
    private ClientInterface $client;
    private array $config;
    protected InputInterface $input;
    protected OutputInterface $output;

    /**
     * AbstractCommand constructor.
     *
     * @param ClientInterface $client
     * @param array $config
     */
    public function __construct(ClientInterface $client, array $config)
    {
        parent::__construct();

        $this->client = $client;
        $this->config = $config;
    }

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
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * @return ClientInterface
     */
    protected function getClient(): ClientInterface
    {
        if ($this->input->getOption('config') || $this->input->getOption('hosts')) {
            return ClientFactory::fromArray($this->getClientConfig());
        }

        return $this->client;
    }

    /**
     * @return array{hosts: list<string>}
     */
    protected function getClientConfig(): array
    {
        $clientConfig = [
            'hosts' => ['localhost']
        ];

        if ($this->input->hasOption('config')) {
            $config = $this->config;

            if (isset($config[$this->input->getOption('config')]['hosts'])) {
                $clientConfig['hosts'] = $config[$this->input->getOption('config')]['hosts'];
            }
        }

        if ($this->input->hasOption('hosts')) {
            $clientConfig['hosts'] = explode(',', $this->input->getOption('hosts'));
        }

        return $clientConfig;
    }
}
