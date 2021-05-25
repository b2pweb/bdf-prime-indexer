<?php

namespace Bdf\Prime\Indexer;

use Bdf\Prime\Prime;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * CommandTestCase
 */
abstract class CommandTestCase extends TestCase
{
    /**
     * @var Application
     */
    protected $app;
    
    /**
     * @var Command
     */
    protected $command;
    
    /**
     * @var CommandTester
     */
    protected $tester;

    protected function setUp(): void
    {
        $kernel = new TestKernel('dev', false);
        $kernel->boot();
        $this->app = new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel);

        Prime::configure($kernel->getContainer()->get('prime'));
    }

    protected function tearDown(): void
    {
        Prime::configure(null);
    }

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
            $tester->setInputs((array)$options['inputs']);
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
            $this->command = $this->app->get($class);
        }

        if ($this->command->getHelperSet() === null) {
            $this->command->setHelperSet((new BaseApplication())->getHelperSet());
        }

        return $this->command;
    }
}