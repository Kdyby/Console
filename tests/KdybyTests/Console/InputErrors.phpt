<?php

/**
 * Test: Kdyby\Console\Application.
 *
 * @testCase KdybyTests\Console\ApplicationTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Console
 */

namespace KdybyTests\Console;

use Kdyby;
use Nette;
use Symfony;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\ApplicationTester;
use Tester;
use Tester\Assert;
use Tracy;
use Tracy\Debugger;



require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/CliAppTester.php';

/**
 * @author Filip Procházka <filip@prochazka.su>
 * @author Michal Gebauer <mishak@mishak.net>
 */
class InputErrorsTest extends Tester\TestCase
{

	private function prepareConfigurator()
	{
		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(array('container' => array('class' => 'SystemContainer_' . Nette\Utils\Strings::random())));
		Kdyby\Console\DI\ConsoleExtension::register($config);
		Kdyby\Events\DI\EventsExtension::register($config);
		$config->addConfig(__DIR__ . '/config/input-errors.neon', $config::NONE);
		$config->addConfig(__DIR__ . '/config/allow.neon', $config::NONE);

		return $config;
	}



	public function testNotLoggingUnknownCommand()
	{
		Debugger::setLogger(new TestLogger('Command "%S%" is not defined.'));
		Debugger::$logDirectory = TEMP_DIR . '/log';
		Tester\Helpers::purge(Debugger::$logDirectory);

		/** @var Nette\DI\Container $container */
		$container = $this->prepareConfigurator()->createContainer();

		/** @var Kdyby\Events\EventManager $evm */
		$evm = $container->getByType('Kdyby\Events\EventManager');
		$evm->addEventSubscriber($listener = new ConsoleListener());

		/** @var Kdyby\Console\Application $app */
		$app = $container->getByType('Kdyby\Console\Application');
		$tester = new ApplicationTester($app);

		Assert::same(Kdyby\Console\Application::INPUT_ERROR_EXIT_CODE, $tester->run(array('tipo')));
		Assert::same(array(), $listener->calls);
	}



	public function getAmbiguousCommandData()
	{
		return array(
			array(array('ambiguous'), 'Command "%S%" is ambiguous (%A%).'),
			array(array('name:ambi'), 'Command "%S%" is ambiguous (%A%).'),
		);
	}




	/**
	 * @dataProvider getAmbiguousCommandData
	 */
	public function testNotLoggingAmbiguousCommand($arguments, $message)
	{
		Debugger::setLogger(new TestLogger($message));
		Debugger::$logDirectory = TEMP_DIR . '/log';
		Tester\Helpers::purge(Debugger::$logDirectory);

		/** @var Nette\DI\Container $container */
		$container = $this->prepareConfigurator()->createContainer();

		/** @var Kdyby\Events\EventManager $evm */
		$evm = $container->getByType('Kdyby\Events\EventManager');
		$evm->addEventSubscriber($listener = new ConsoleListener());

		/** @var Kdyby\Console\Application $app */
		$app = $container->getByType('Kdyby\Console\Application');
		$tester = new ApplicationTester($app);

		Assert::same(Kdyby\Console\Application::INPUT_ERROR_EXIT_CODE, $tester->run($arguments));
		Assert::same(array(), $listener->calls);
	}



	public function getNotLoggingUnknownArgumentData()
	{
		return array(
			array(array('arg'), 'Not enough arguments.'),
			array(array('arg', 'first', 'second', 'third'), 'Too many arguments.'),
			array(array('arg', '--non-existent-option', 'first'), 'The "--%a%" option does not exist.'),
			array(array('arg', '-aa', 'first'), 'The "-%a%" option does not exist.'),
			array(array('arg', '--no-value=1', 'first'), 'The "--%a%" option does not accept a value.'),
			array(array('arg', '--existent', '--no-value', 'first'), 'The "--%a%" option requires a value.'),
		);
	}



	/**
	 * @dataProvider getNotLoggingUnknownArgumentData
	 */
	public function testNotLoggingUnknownArgument($arguments, $message)
	{
		Debugger::setLogger(new TestLogger($message));
		Debugger::$logDirectory = TEMP_DIR . '/log';
		Tester\Helpers::purge(Debugger::$logDirectory);

		/** @var Nette\DI\Container $container */
		$container = $this->prepareConfigurator()->createContainer();

		/** @var Kdyby\Events\EventManager $evm */
		$evm = $container->getByType('Kdyby\Events\EventManager');
		$evm->addEventSubscriber($listener = new ConsoleListener());

		/** @var Kdyby\Console\Application $app */
		$app = $container->getByType('Kdyby\Console\Application');
		$tester = new CliAppTester($app);

		array_unshift($arguments, 'www/index.php');
		Assert::same(Kdyby\Console\Application::INPUT_ERROR_EXIT_CODE, $tester->run($arguments));
		$calls = $listener->calls;
		$last = array_pop($calls); // exception record
		Assert::same(array(
			array('command', 'KdybyTests\\Console\\ArgCommand'),
			array('terminate', 'KdybyTests\\Console\\ArgCommand', 0),
		), $calls);
		array_pop($last); // thrown exception
		Assert::same(array('exception', 'KdybyTests\\Console\\ArgCommand'), $last);
	}

}



class ConsoleListener extends Nette\Object implements Kdyby\Events\Subscriber
{

	public $calls = array();


	public function getSubscribedEvents()
	{
		return array(
			ConsoleEvents::COMMAND,
			ConsoleEvents::EXCEPTION,
			ConsoleEvents::TERMINATE,
		);
	}



	public function command(ConsoleCommandEvent $event)
	{
		$this->calls[] = array(__FUNCTION__, get_class($event->getCommand()));
	}



	public function exception(ConsoleExceptionEvent $event)
	{
		$this->calls[] = array(__FUNCTION__, get_class($event->getCommand()), $event->getException());
	}



	public function terminate(ConsoleTerminateEvent $event)
	{
		$this->calls[] = array(__FUNCTION__, get_class($event->getCommand()), $event->getExitCode());
	}

}



class TestLogger extends Tracy\Logger
{

	public $messages = array();



	public function __construct($pattern)
	{
		$this->pattern = $pattern;
	}



	public function log($value, $priority = 'info')
	{
		$this->messages[] = func_get_args();
		Assert::match('%A?%' . $this->pattern, implode((array)$value));
	}
}



class ArgCommand extends Symfony\Component\Console\Command\Command
{

	protected function configure()
	{
		$this->setName('arg')
			->addArgument('first', Symfony\Component\Console\Input\InputArgument::REQUIRED)
			->addArgument('second')
			->addOption('existent', 'e', Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED)
			->addOption('no-value', 'x');
	}



	protected function execute(InputInterface $input, OutputInterface $output)
	{
		Assert::fail("This command shouldn't have been executed.");
	}

}



class AmbiguousCommand1 extends Symfony\Component\Console\Command\Command
{

	protected function configure()
	{
		$this->setName('ambiguous1');
	}



	protected function execute(InputInterface $input, OutputInterface $output)
	{
		Assert::fail("This command shouldn't have been executed.");
	}

}



class AmbiguousCommand2 extends Symfony\Component\Console\Command\Command
{

	protected function configure()
	{
		$this->setName('ambiguous2');
	}



	protected function execute(InputInterface $input, OutputInterface $output)
	{
		Assert::fail("This command shouldn't have been executed.");
	}

}



class NamespaceAmbiguousCommand1 extends Symfony\Component\Console\Command\Command
{

	protected function configure()
	{
		$this->setName('namespace1:ambiguous');
	}



	protected function execute(InputInterface $input, OutputInterface $output)
	{
		Assert::fail("This command shouldn't have been executed.");
	}

}



class NamespaceAmbiguousCommand2 extends Symfony\Component\Console\Command\Command
{

	protected function configure()
	{
		$this->setName('namespace2:ambiguous');
	}



	protected function execute(InputInterface $input, OutputInterface $output)
	{
		Assert::fail("This command shouldn't have been executed.");
	}

}



class TypoCommand extends Symfony\Component\Console\Command\Command
{

	protected function configure()
	{
		$this->setName('typo');
	}



	protected function execute(InputInterface $input, OutputInterface $output)
	{
		Assert::fail("This command shouldn't have been executed.");
	}

}

\run(new InputErrorsTest());
