<?php

/**
 * Test: Kdyby\Console\Application.
 *
 * @testCase
 */

namespace KdybyTests\Console;

use Kdyby\Console\Application;
use Kdyby\Console\DI\ConsoleExtension;
use Kdyby\Console\FatalThrowableError;
use Kdyby\Events\DI\EventsExtension;
use Kdyby\Events\EventManager;
use Nette\Configurator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\ListCommand;
use Symfony\Component\Console\Tester\ApplicationTester;
use Tester\Assert;
use Tester\Environment as TesterEnvironment;

require_once __DIR__ . '/../bootstrap.php';

class ApplicationTest extends \Tester\TestCase
{

	private function prepareConfigurator()
	{
		$config = new Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(['container' => ['class' => 'SystemContainer_' . md5(mt_rand())]]);
		ConsoleExtension::register($config);
		EventsExtension::register($config);
		$config->addConfig(__DIR__ . '/config/allow.neon');
		$config->addConfig(__DIR__ . '/../nette-reset.neon');

		return $config;
	}

	public function testDelegateEventsToSymfonyDispatcher()
	{
		/** @var \Nette\DI\Container $container */
		$container = $this->prepareConfigurator()->createContainer();

		/** @var \Kdyby\Events\EventManager $evm */
		$evm = $container->getByType(EventManager::class);
		$evm->addEventSubscriber($listener = new ConsoleListener());

		/** @var \Kdyby\Console\Application $app */
		$app = $container->getByType(Application::class);
		$tester = new ApplicationTester($app);

		Assert::same(0, $tester->run(['list']));
		Assert::same([
			['command', ListCommand::class],
			['terminate', ListCommand::class, 0],
		], $listener->calls);
	}

	public function testRenderThrowable()
	{
		if (PHP_VERSION_ID < 70000) {
			TesterEnvironment::skip('Testing throwable is only relevant with PHP >= 7.0');
		}

		/** @var \Nette\DI\Container $container */
		$container = $this->prepareConfigurator()->createContainer();

		/** @var \Kdyby\Console\Application $app */
		$app = $container->getByType(Application::class);

		$command = new Command('fail');
		$command->setCode(function () {
			throw new \ParseError('Fuuuuck', 42);
		});
		$app->add($command);

		$tester = new ApplicationTester($app);
		$exitCode = $tester->run(['fail']);
		Assert::same(42, $exitCode);

		$output = $tester->getDisplay();
		Assert::contains(FatalThrowableError::class, $output);
		Assert::contains('Fuuuuck', $output);
	}

}

(new ApplicationTest())->run();
