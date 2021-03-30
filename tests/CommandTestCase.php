<?php

namespace Bdf\Prime\Indexer;

use Bdf\DI\Container;
use Bdf\DI\DIAccessorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * CommandTestCase
 */
abstract class CommandTestCase extends TestCase
{
    /**
     * @var Container
     */
    protected $di;
    
    /**
     * @var Command
     */
    protected $command;
    
    /**
     * @var CommandTester
     */
    protected $tester;

    /**
     * Execute a command by its name
     *
     * @param string|Command $command
     * @param array  $input
     * @param array  $options
     *
     * @return string  Returns display from command
     */
    public function execute($command, array $input = [], array $options = [])
    {
        $tester = $this->createCommandTester($this->createCommand($command));
        if (isset($options['inputs'])) {
            if (method_exists($tester, 'setInputs')) {
                // symfony >= 3.2
                $tester->setInputs((array)$options['inputs']);
            } else {
                $this->command->getHelperSet()->get('question')->setInputStream($this->getInputStream((array)$options['inputs']));
            }
        }
        $tester->execute($input, $options);

        return $tester->getDisplay();
    }

    /**
     * Inject input in stream
     *
     * @param array $input
     * @return string
     */
    protected function getInputStream(array $input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, implode(PHP_EOL, $input));
        rewind($stream);

        return $stream;
    }

    /**
     * @param Command $command
     * @return CommandTester
     */
    protected function createCommandTester(Command $command)
    {
        return $this->tester = new CommandTester($command);
    }
    
    /**
     * Instanciate command
     * 
     * @param string $class
     * @return Command
     */
    public function createCommand($class)
    {
        if ($class instanceof Command) {
            $this->command = $class;
        } else {
            $this->command = $this->di->get('di.instantiator')->make($class);
            
            if ($this->command instanceof DIAccessorInterface) {
                $this->command->setDI($this->di);
            }
        }

        if ($this->command->getHelperSet() === null) {
            $this->command->setHelperSet((new BaseApplication())->getHelperSet());
        }

        return $this->command;
    }
}