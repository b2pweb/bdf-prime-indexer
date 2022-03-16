<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class ShowCommand
 */
class ShowCommand extends AbstractCommand
{
    protected static $defaultName = 'elasticsearch:show';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setDescription('Affiche la liste des index');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new SymfonyStyle($input, $output);
        $client = $this->getClient();

        $aliases = $client->indices()->getAlias();

        $headers = ['Indices', 'Properties', 'Aliases'];
        $rows = [];

        foreach ($client->indices()->getMapping() as $index => $definition) {
            $rows[] = [$index, $this->getProperties($definition), $this->getAliasesColumn($aliases, $index)];
        }

        usort($rows, function ($a, $b) {
            return strcmp($a[0], $b[0]);
        });

        $style->createTable()
            ->setStyle('box-double')
            ->setHeaders($headers)
            ->setRows($rows)
            ->render()
        ;

        $style->newLine();

        return 0;
    }

    /**
     * @param array $definition
     *
     * @return string
     */
    protected function getProperties(array $definition)
    {
        if (empty($definition['mappings']['properties'])) {
            return '';
        }

        $properties = '';

        foreach ($definition['mappings']['properties'] as $prop => $def) {
            $properties .= $prop . ': ' . $def['type'] . PHP_EOL;
        }

        return $properties;
    }

    /**
     * @param array $aliases
     * @param string $index
     *
     * @return string
     */
    protected function getAliasesColumn(array $aliases, $index)
    {
        if (empty($aliases[$index]) || empty($aliases[$index]['aliases'])) {
            return '';
        }

        $indexAliases = array_keys($aliases[$index]['aliases']);

        sort($indexAliases);

        return implode(PHP_EOL, $indexAliases);
    }
}
