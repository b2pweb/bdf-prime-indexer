<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Console;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractCommand
 */
class AbstractCommand extends Command
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var array
     */
    private $config;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * AbstractCommand constructor.
     *
     * @param Client $client
     * @param array $config
     */
    public function __construct(Client $client, array $config)
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
     * @return Client
     */
    protected function getClient(): Client
    {
        if ($this->input->getOption('config') || $this->input->getOption('hosts')) {
            return ClientBuilder::fromConfig($this->getClientConfig());
        }

        return $this->client;
    }

    /**
     * @return array
     */
    protected function getClientConfig()
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
