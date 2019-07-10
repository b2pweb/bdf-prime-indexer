<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Console;

/**
 * Class ShowCommand
 */
class ShowCommand extends AbstractCommand
{
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
    public static function names()
    {
        return ['elasticsearch:show'];
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute()
    {
        $client = $this->getClient();

        $aliases = $client->indices()->getAliases();

        $headers = ['Indices', 'Types', 'Aliases'];
        $rows = [];

        foreach ($client->indices()->getMapping() as $index => $definition) {
            $rows[] = [$index, $this->getTypesColumn($definition), $this->getAliasesColumn($aliases, $index)];
        }

        usort($rows, function($a, $b) {
            return strcmp($a[0], $b[0]);
        });

        $this->table($headers, $rows);
    }

    /**
     * @param array $definition
     *
     * @return string
     */
    protected function getTypesColumn(array $definition)
    {
        if (empty($definition['mappings'])) {
            return '';
        }

        $types = array_keys($definition['mappings']);

        sort($types);

        return implode(PHP_EOL, $types);
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
