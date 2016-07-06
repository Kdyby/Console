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
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Tester\ApplicationTester;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ApplicationTest extends Tester\TestCase
{

	private function prepareConfigurator()
	{
		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(['container' => ['class' => 'SystemContainer_' . md5(rand())]]);
		Kdyby\Console\DI\ConsoleExtension::register($config);
		Kdyby\Events\DI\EventsExtension::register($config);
		$config->addConfig(__DIR__ . '/config/allow.neon');

		return $config;
	}



	public function testDelegateEventsToSymfonyDispatcher()
	{
		/** @var Nette\DI\Container $container */
		$container = $this->prepareConfigurator()->createContainer();

		/** @var Kdyby\Events\EventManager $evm */
		$evm = $container->getByType('Kdyby\Events\EventManager');
		$evm->addEventSubscriber($listener = new ConsoleListener());

		/** @var Kdyby\Console\Application $app */
		$app = $container->getByType('Kdyby\Console\Application');
		$tester = new ApplicationTester($app);

		Assert::same(0, $tester->run(['list']));
		Assert::same([
			['command', 'Symfony\\Component\\Console\\Command\\ListCommand'],
			['terminate', 'Symfony\\Component\\Console\\Command\\ListCommand', 0],
		], $listener->calls);
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

\run(new ApplicationTest());
