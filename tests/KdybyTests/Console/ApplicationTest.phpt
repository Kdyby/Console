<?php

declare(strict_types = 1);

namespace KdybyTests\Console;

use Kdyby\Console\Application;
use Kdyby\Events\EventManager;
use Nette\Configurator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\ListCommand;
use Symfony\Component\Console\Tester\ApplicationTester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

/**
 * Test: Kdyby\Console\Application.
 *
 * @testCase
 */
class ApplicationTest extends \Tester\TestCase
{

	private function prepareConfigurator(): Configurator
	{
		$config = new Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(['container' => ['class' => 'SystemContainer_' . md5((string) mt_rand())]]);
		$config->onCompile[] = static function ($config, \Nette\DI\Compiler $compiler): void {
			$compiler->addExtension('console', new \Kdyby\Console\DI\ConsoleExtension());
		};
		$config->onCompile[] = static function ($config, \Nette\DI\Compiler $compiler): void {
			$compiler->addExtension('events', new \Kdyby\Events\DI\EventsExtension());
		};
		$config->addConfig(__DIR__ . '/config/allow.neon');
		$config->addConfig(__DIR__ . '/../nette-reset.neon');

		return $config;
	}

	public function testDelegateEventsToSymfonyDispatcher(): void
	{
		/** @var \Nette\DI\Container $container */
		$container = $this->prepareConfigurator()->createContainer();

		/** @var \Kdyby\Events\EventManager $evm */
		$evm = $container->getByType(EventManager::class);
		$evm->addEventSubscriber($listener = new ConsoleListener());

		/** @var \Kdyby\Console\Application $app */
		$app = $container->getByType(Application::class);
		$tester = new ApplicationTester($app);

		Assert::same(0, $tester->run(['command' => 'list']));
		Assert::same([
			['command', ListCommand::class],
			['terminate', ListCommand::class, 0],
		], $listener->calls);
	}

	/**
	 * @phpVersion >= 7.0
	 */
	public function testRenderThrowable(): void
	{

		/** @var \Nette\DI\Container $container */
		$container = $this->prepareConfigurator()->createContainer();

		/** @var \Kdyby\Console\Application $app */
		$app = $container->getByType(Application::class);

		$command = new Command('fail');
		$command->setCode(function (): void {
			throw new \ParseError('Fuuuuck', 42);
		});
		$app->add($command);

		$tester = new ApplicationTester($app);
		$exitCode = $tester->run(['command' => 'fail']);
		Assert::same(42, $exitCode);

		$output = $tester->getDisplay();
		Assert::contains('Fuuuuck', $output);
	}

}

(new ApplicationTest())->run();
