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
 * @author Michal Gebauer <mishak@mishak.net>
 */
class ApplicationTest extends Tester\TestCase
{

	private function prepareConfigurator()
	{
		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(array('container' => array('class' => 'SystemContainer_' . Nette\Utils\Strings::random())));
		Kdyby\Console\DI\ConsoleExtension::register($config);
		Kdyby\Events\DI\EventsExtension::register($config);
		$config->addConfig(__DIR__ . '/config/allow.neon', $config::NONE);

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

		Assert::same(0, $tester->run(array('list')));
		Assert::same(array(
			 array('command', 'Symfony\\Component\\Console\\Command\\ListCommand'),
			 array('terminate', 'Symfony\\Component\\Console\\Command\\ListCommand', 0),
		), $listener->calls);
	}



	public function testNotLoggingUnknownCommand()
	{
		Nette\Diagnostics\Debugger::$logger = new TestLogger('Command "%S%" is not defined.');
		Nette\Diagnostics\Debugger::$logDirectory = TEMP_DIR . '/log';
		Tester\Helpers::purge(Nette\Diagnostics\Debugger::$logDirectory);

		/** @var Nette\DI\Container $container */
		$container = $this->prepareConfigurator()->createContainer();

		/** @var Kdyby\Events\EventManager $evm */
		$evm = $container->getByType('Kdyby\Events\EventManager');
		$evm->addEventSubscriber($listener = new ConsoleListener());

		/** @var Kdyby\Console\Application $app */
		$app = $container->getByType('Kdyby\Console\Application');
		$tester = new ApplicationTester($app);

		Assert::same(253, $tester->run(array('lyst'))); # intentionally y instead of i to simulate user error
		Assert::same(array(), $listener->calls);
	}



	public function testNotLoggingAmbiguousCommand()
	{
		Nette\Diagnostics\Debugger::$logger = new TestLogger('Command "%S%" is ambiguous (%S%).');
		Nette\Diagnostics\Debugger::$logDirectory = TEMP_DIR . '/log';
		Tester\Helpers::purge(Nette\Diagnostics\Debugger::$logDirectory);

		/** @var Nette\DI\Container $container */
		$container = $this->prepareConfigurator()->createContainer();

		/** @var Kdyby\Events\EventManager $evm */
		$evm = $container->getByType('Kdyby\Events\EventManager');
		$evm->addEventSubscriber($listener = new ConsoleListener());

		/** @var Kdyby\Console\Application $app */
		$app = $container->getByType('Kdyby\Console\Application');
		$tester = new ApplicationTester($app);

		Assert::same(253, $tester->run(array('s'))); # intentionally s instead of i to simulate ambiguity (li[s]t and help)
		Assert::same(array(), $listener->calls);
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



class TestLogger
{
	function __construct($pattern)
	{
		$this->pattern = $pattern;
	}

	public function log($message)
	{
		Assert::match($this->pattern, $message[1]);
	}
}

\run(new ApplicationTest());
