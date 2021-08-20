<?php

declare(strict_types = 1);

/**
 * Test: Kdyby\Console\Application.
 *
 * @testCase
 */

namespace KdybyTests\Console;

use Kdyby\Console\Application;
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

	private function prepareConfigurator(): Configurator
	{
		$config = new Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(['container' => ['class' => 'SystemContainer_' . md5((string)mt_rand())]]);
		$config->onCompile[] = static function ($config, \Nette\DI\Compiler $compiler): void {
			$compiler->addExtension('console', new \Kdyby\Console\DI\ConsoleExtension());
		};
		$config->onCompile[] = static function ($config, \Nette\DI\Compiler $compiler): void {
			$compiler->addExtension('events', new \Kdyby\Events\DI\EventsExtension());
		};
		$config->addConfig(__DIR__ . '/config/input-errors.neon');
		$config->addConfig(__DIR__ . '/config/allow.neon');
		$config->addConfig(__DIR__ . '/../nette-reset.neon');

		return $config;
	}

	public function testNotLoggingUnknownCommand(): void
	{
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

		Assert::same(Application::INPUT_ERROR_EXIT_CODE, $tester->run(['command' => 'tipo']));
		Assert::same([], $listener->calls);
	}

	/**
	 * @return mixed[]
	 */
	public function getAmbiguousCommandData(): array
	{
		return [
			[['command' => 'ambiguous'], '%a%ambiguous%a%'],
			[['command' => 'name:ambi'], '%a%ambiguous%a%'],
		];
	}

	/**
	 * @dataProvider getAmbiguousCommandData
	 * @param string[] $arguments
	 * @param string $message
	 */
	public function testNotLoggingAmbiguousCommand(array $arguments, string $message): void
	{
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
	public function getNotLoggingUnknownArgumentData(): array
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
	 * @param string[] $arguments
	 * @param string $message
	 */
	public function testNotLoggingUnknownArgument(array $arguments, string $message): void
	{
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

		Assert::count(2, $listener->calls);
		Assert::same('command', $listener->calls[0][0]);
		Assert::same('terminate', $listener->calls[1][0]);
	}

}

(new InputErrorsTest())->run();
