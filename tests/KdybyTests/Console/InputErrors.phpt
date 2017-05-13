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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
		$config->addParameters(['container' => ['class' => 'SystemContainer_' . md5(mt_rand())]]);
		Kdyby\Console\DI\ConsoleExtension::register($config);
		Kdyby\Events\DI\EventsExtension::register($config);
		$config->addConfig(__DIR__ . '/config/input-errors.neon');
		$config->addConfig(__DIR__ . '/config/allow.neon');
		$config->addConfig(__DIR__ . '/../../nette-reset.neon');

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

		Assert::same(Kdyby\Console\Application::INPUT_ERROR_EXIT_CODE, $tester->run(['tipo']));
		Assert::same([], $listener->calls);
	}



	public function getAmbiguousCommandData()
	{
		return [
			[['ambiguous'], '%a% ambiguous %a%'],
			[['name:ambi'], '%a% ambiguous %a%'],
		];
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
		Assert::same([], $listener->calls);
	}



	public function getNotLoggingUnknownArgumentData()
	{
		return [
			[['arg'], 'Not enough arguments%A?%'],
			[['arg', 'first', 'second', 'third'], 'Too many arguments.'],
			[['arg', '--non-existent-option', 'first'], 'The "--%a%" option does not exist.'],
			[['arg', '-aa', 'first'], 'The "-%a%" option does not exist.'],
			[['arg', '--no-value=1', 'first'], 'The "--%a%" option does not accept a value.'],
			[['arg', '--existent', '--no-value', 'first'], 'The "--%a%" option requires a value.'],
		];
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

		try {
			Assert::same(Kdyby\Console\Application::INPUT_ERROR_EXIT_CODE, $tester->run($arguments));
		} catch (Tester\AssertException $e) {
			Tester\Environment::skip($e->getMessage());
		}

		Assert::count(3, $listener->calls);
		Assert::same('command', $listener->calls[0][0]);
		try {
			Assert::same('exception', $listener->calls[1][0]);
			Assert::same('terminate', $listener->calls[2][0]);
		} catch (Tester\AssertException $e) {
			Assert::same('terminate', $listener->calls[1][0]);
			Assert::same('exception', $listener->calls[2][0]);
		}
	}

}



class ConsoleListener extends Nette\Object implements Kdyby\Events\Subscriber
{

	public $calls = [];


	public function getSubscribedEvents()
	{
		return [
			ConsoleEvents::COMMAND,
			ConsoleEvents::EXCEPTION,
			ConsoleEvents::TERMINATE,
		];
	}



	public function command(ConsoleCommandEvent $event)
	{
		$this->calls[] = [__FUNCTION__, get_class($event->getCommand())];
	}



	public function exception(ConsoleExceptionEvent $event)
	{
		$this->calls[] = [__FUNCTION__, get_class($event->getCommand()), $event->getException()];
	}



	public function terminate(ConsoleTerminateEvent $event)
	{
		$this->calls[] = [__FUNCTION__, get_class($event->getCommand()), $event->getExitCode()];
	}

}



class TestLogger extends Tracy\Logger
{

	public $messages = [];



	public function __construct($pattern)
	{
		$this->pattern = $pattern;
	}



	public function log($value, $priority = 'info')
	{
		if ($value instanceof \Exception) {
			throw $value;
		}

		$this->messages[] = func_get_args();
		Assert::match('%A?%' . $this->pattern, implode((array)$value));
	}
}



class ArgCommand extends Symfony\Component\Console\Command\Command
{

	protected function configure()
	{
		$this->setName('arg')
			->addArgument('first', InputArgument::REQUIRED)
			->addArgument('second')
			->addOption('existent', 'e', InputOption::VALUE_REQUIRED)
			->addOption('no-value', 'x', InputOption::VALUE_NONE);
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

class SameArgsCommandOne extends Symfony\Component\Console\Command\Command
{

	public function __construct(ArgCommand $argCommand, TypoCommand $typoCommand)
	{
		parent::__construct();
	}


	protected function configure()
	{
		$this->setName('sameArgsCommand:one');
	}



	protected function execute(InputInterface $input, OutputInterface $output)
	{
		Assert::fail("This command shouldn't have been executed.");
	}

}


class SameArgsCommandTwo extends Symfony\Component\Console\Command\Command
{

	public function __construct(ArgCommand $argCommand, TypoCommand $typoCommand)
	{
		parent::__construct();
	}

	protected function configure()
	{
		$this->setName('sameArgsCommand:two');
	}



	protected function execute(InputInterface $input, OutputInterface $output)
	{
		Assert::fail("This command shouldn't have been executed.");
	}

}

(new InputErrorsTest())->run();
