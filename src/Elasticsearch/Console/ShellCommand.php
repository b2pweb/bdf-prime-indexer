<?php

namespace Bdf\Prime\Indexer\Elasticsearch\Console;

use Bdf\Console\Commands\ExitCommand;
use Bdf\Console\ShellCommand as BaseShellCommand;

/**
 * Class ShellCommand
 */
class ShellCommand extends BaseShellCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('elasticsearch')
            ->setDescription('Elasticsearch Shell')
            ->addCommands([
                new ShowCommand(),
                new DeleteCommand(),
                new ExitCommand(),
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public static function names()
    {
        return ['elasticsearch'];
    }
}
