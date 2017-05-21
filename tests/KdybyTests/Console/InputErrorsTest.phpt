<?php

/**
 * Test: Kdyby\Console\Application.
 *
 * @testCase
 */

namespace KdybyTests\Console;

use Kdyby\Console\Application;
use Kdyby\Console\DI\ConsoleExtension;
use Kdyby\Events\DI\EventsExtension;
use Kdyby\Events\EventManager;
use Nette\Configurator;
use Symfony\Component\Console\Tester\ApplicationTester;
use Tester\Assert;
use Tester\Environment as TesterEnvironment;
use Tester\Helpers as TesterHelpers;
use Tracy\Debugger;

require_once __DIR__ . '/../bootstrap.php';

class InputErrorsTest extends \Tester\TestCase
{

	private function prepareConfigurator()
	{
		$config = new Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(['container' => ['class' => 'SystemContainer_' . md5(mt_rand())]]);
		ConsoleExtension::register($config);
		EventsExtension::register($config);
		$config->addConfig(__DIR__ . '/config/input-errors.neon');
		$config->addConfig(__DIR__ . '/config/allow.neon');
		$config->addConfig(__DIR__ . '/../nette-reset.neon');

		return $config;
	}

	public function testNotLoggingUnknownCommand()
	{
		Debugger::setLogger(new TestLogger('Command "%S%" is not defined.'));
		Debugger::$logDirectory = TEMP_DIR . '/log';
		TesterHelpers::purge(Debugger::$logDirectory);

		/** @var \Nette\DI\Container $container */
		$container = $this->prepareConfigurator()->createContainer();

		/** @var \Kdyby\Events\EventManager $evm */
		$evm = $container->getByType(EventManager::class);
		$evm->addEventSubscriber($listener = new ConsoleListener());

		/** @var \Kdyby\Console\Application $app */
		$app = $container->getByType(Application::class);
		$tester = new ApplicationTester($app);

		Assert::same(Application::INPUT_ERROR_EXIT_CODE, $tester->run(['tipo']));
		Assert::same([], $listener->calls);
	}

	/**
	 * @return mixed[]
	 */
	public function getAmbiguousCommandData()
	{
		return [
			[['ambiguous'], '%a%ambiguous%a%'],
			[['name:ambi'], '%a%ambiguous%a%'],
		];
	}

	/**
	 * @dataProvider getAmbiguousCommandData
	 *
	 * @param string[] $arguments
	 * @param string $message
	 */
	public function testNotLoggingAmbiguousCommand(array $arguments, $message)
	{
		Debugger::setLogger(new TestLogger($message));
		Debugger::$logDirectory = TEMP_DIR . '/log';
		TesterHelpers::purge(Debugger::$logDirectory);

		/** @var \Nette\DI\Container $container */
		$container = $this->prepareConfigurator()->createContainer();

		/** @var \Kdyby\Events\EventManager $evm */
		$evm = $container->getByType(EventManager::class);
		$evm->addEventSubscriber($listener = new ConsoleListener());

		/** @var \Kdyby\Console\Application $app */
		$app = $container->getByType(Application::class);
		$tester = new ApplicationTester($app);

		Assert::same(Application::INPUT_ERROR_EXIT_CODE, $tester->run($arguments));
		Assert::same([], $listener->calls);
	}

	/**
	 * @return mixed[]
	 */
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
	 *
	 * @param string[] $arguments
	 * @param string $message
	 */
	public function testNotLoggingUnknownArgument(array $arguments, $message)
	{
		Debugger::setLogger(new TestLogger($message));
		Debugger::$logDirectory = TEMP_DIR . '/log';
		TesterHelpers::purge(Debugger::$logDirectory);

		/** @var \Nette\DI\Container $container */
		$container = $this->prepareConfigurator()->createContainer();

		/** @var \Kdyby\Events\EventManager $evm */
		$evm = $container->getByType(EventManager::class);
		$evm->addEventSubscriber($listener = new ConsoleListener());

		/** @var \Kdyby\Console\Application $app */
		$app = $container->getByType(Application::class);
		$tester = new CliAppTester($app);

		array_unshift($arguments, 'bin/console');

		try {
			Assert::same(Application::INPUT_ERROR_EXIT_CODE, $tester->run($arguments));
		} catch (\Tester\AssertException $e) {
			TesterEnvironment::skip($e->getMessage());
		}

		Assert::count(3, $listener->calls);
		Assert::same('command', $listener->calls[0][0]);
		try {
			Assert::same('exception', $listener->calls[1][0]);
			Assert::same('terminate', $listener->calls[2][0]);
		} catch (\Tester\AssertException $e) {
			Assert::same('terminate', $listener->calls[1][0]);
			Assert::same('exception', $listener->calls[2][0]);
		}
	}

}

(new InputErrorsTest())->run();
