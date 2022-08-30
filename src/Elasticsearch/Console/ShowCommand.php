<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Console;

use Bdf\Prime\Indexer\Elasticsearch\Adapter\Response\Aliases;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class ShowCommand
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[AsCommand('elasticsearch:show', 'Affiche la liste des index')]
class ShowCommand extends AbstractCommand
{
    protected static $defaultName = 'elasticsearch:show';

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Affiche la liste des index');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $client = $this->getClient();

        $aliases = $client->getAllAliases();

        $headers = ['Indices', 'Properties', 'Aliases'];
        $rows = [];

        foreach ($client->getAllIndexesMapping() as $index => $definition) {
            $rows[] = [$index, $this->getProperties($definition), $this->getAliasesColumn($aliases, $index)];
        }

        usort($rows, fn($a, $b) => strcmp($a[0], $b[0]));

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
    protected function getProperties(array $definition): string
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
     * @param array<string, Aliases> $aliases
     * @param string $index
     *
     * @return string
     */
    protected function getAliasesColumn(array $aliases, string $index): string
    {
        if (empty($aliases[$index])) {
            return '';
        }

        $indexAliases = $aliases[$index]->all();

        sort($indexAliases);

        return implode(PHP_EOL, $indexAliases);
    }
}
