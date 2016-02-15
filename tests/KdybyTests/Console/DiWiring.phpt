<?php

/**
 * Test: Full DI tests for commands
 *
 * @testCase Kdyby\Console\DiWiring
 * @author Pavel Ptacek <ptacek.pavel@gmail.com>
 * @package Kdyby\Console
 */

namespace KdybyTests\Console\DI;

use Kdyby;
use Nette;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Tester;
use Tester\Assert;
use Tracy\Debugger;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/CliAppTester.php';



/**
 * @author Pavel Ptacek <ptacek.pavel@gmail.com>
 */
class DiWiringTest extends Tester\TestCase
{
	/**
	 * @return Nette\DI\Container
	 */
	private function createContainer()
	{
		Debugger::$logDirectory = TEMP_DIR . '/log';
		Tester\Helpers::purge(Debugger::$logDirectory);

		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->setDebugMode(TRUE);
		$config->addConfig(__DIR__ . '/config/di-wiring.neon', $config::NONE);
		$config->addConfig(__DIR__ . '/config/allow.neon', $config::NONE);

		return $config->createContainer();
	}

	/**
	 * Create containers from configuration
	 */
	public function testContainerCreation()
	{
		$app = $this->createContainer()->getService('console.application');
		Assert::true(
			$app instanceof Kdyby\Console\Application,
			'Application was not created using container->getService(console.application)'
		);

		$app = $this->createContainer()->getByType('Kdyby\Console\Application');
		Assert::true(
			$app instanceof Kdyby\Console\Application,
			'Application was not created using container->getByType(Kdyby\Console\Application)'
		);
	}

	/**
	 * Create commands and get their execution
	 */
	public function testCommands()
	{
		/** @var $app Kdyby\Console\Application */
		$app = $this->createContainer()->getByType('Kdyby\Console\Application');

		$tests = [
			CommandInCommands::COMMAND_NAME => CommandInCommands::RETURN_CODE,
			CommandAsService::COMMAND_NAME => CommandAsService::RETURN_CODE,
			CommandInCommandsNeedsAll::COMMAND_NAME => CommandInCommandsNeedsAll::RETURN_CODE,
			CommandAsServiceNeedsAll::COMMAND_NAME => CommandAsServiceNeedsAll::RETURN_CODE,
			CommandInCommandsNeedsAllWithInject::COMMAND_NAME => CommandInCommandsNeedsAllWithInject::RETURN_CODE,
			CommandAsServiceNeedsAllWithInject::COMMAND_NAME => CommandAsServiceNeedsAllWithInject::RETURN_CODE,
		];

		$null = new NullOutput();
		foreach($tests as $command => $returnCode) {
			Assert::same(
				$returnCode,
				$app->run(new ArgvInput(['www/index.php', $command]), $null),
				'Exit code check: '.$command.' did not run properly'
			);
		}
	}

	/**
	 * Validate that helpers are injected properly
	 */
	public function testHelpers()
	{
		/** @var $app Kdyby\Console\Application */
		$app = $this->createContainer()->getByType('Kdyby\Console\Application');

		/** @var $command CommandInCommands */
		$command = $app->find(CommandInCommands::COMMAND_NAME);
		$command->validateHelpers();
	}

	/**
	 * Validate that all injections are properly inserted by DI
	 */
	public function testInjections()
	{
		/** @var $app Kdyby\Console\Application */
		$app = $this->createContainer()->getByType('Kdyby\Console\Application');

		/** @var $command CommandInCommandsNeedsAll */
		$command = $app->get(CommandInCommandsNeedsAll::COMMAND_NAME);
		$command->validateNotAnnotations();
		$command->validateConstructor();
		$command->validateNotInjector();

		/** @var $command CommandAsServiceNeedsAll */
		$command = $app->find(CommandAsServiceNeedsAll::COMMAND_NAME);
		$command->validateNotAnnotations();
		$command->validateConstructor();
		$command->validateNotInjector();

		/** @var $command CommandInCommandsNeedsAll */
		$command = $app->get(CommandInCommandsNeedsAllWithInject::COMMAND_NAME);
		$command->validateAnnotations();
		$command->validateConstructor();
		$command->validateInjector();

		/** @var $command CommandAsServiceNeedsAll */
		$command = $app->find(CommandAsServiceNeedsAllWithInject::COMMAND_NAME);
		$command->validateAnnotations();
		$command->validateConstructor();
		$command->validateInjector();
	}
}

/**
 * Basic test for return code on all commands, test for helper blueprint
 * @package KdybyTests\Console\DI
 */
abstract class DiTestCommand extends Command
{
	const COMMAND_NAME = 'n/a';
	const RETURN_CODE = 10;

	public function __construct()
	{
		parent::__construct(static::COMMAND_NAME);
	}

	public function execute(InputInterface $input, OutputInterface $output)
	{
		return static::RETURN_CODE;
	}

	public function validateHelpers()
	{
		Assert::type('KdybyTests\Console\DI\HelperInSection', $this->getHelper('HelperInSection'), 'helper in section');
		Assert::type('KdybyTests\Console\DI\HelperAsService', $this->getHelper('HelperAsService'), 'helper as service');
	}
}

/**
 * Basic class for dependant commands - those, that use the trait - boilerplate
 * @package KdybyTests\Console\DI
 */
abstract class DiTestDependantCommand extends DiTestCommand
{
	/**
	 * Load dependency tester trait
	 */
	use DependencyTesterTrait {
		DependencyTesterTrait::__construct as private __dttConstruct;
	}

	/**
	 * @param CommandInCommands $commandInCommands
	 * @param TestService $testService
	 * @param CommandAsService $commandAsService
	 */
	public function __construct(
		CommandInCommands $commandInCommands,
		TestService $testService,
		CommandAsService $commandAsService
	)
	{
		parent::__construct();
		$this->__dttConstruct($commandInCommands, $testService, $commandAsService);
	}
}

/**
 * Used for testing the dependencies
 * @package KdybyTests\Console\DI
 */
trait DependencyTesterTrait
{
	/** @var CommandInCommands */
	private $constructorCommandInCommands;

	/** @var TestService */
	private $constructorTestService;

	/** @var CommandAsService */
	private $constructorCommandAsService;

	/** @var CommandInCommands */
	private $injectorCommandInCommands;

	/** @var TestService */
	private $injectorTestService;

	/** @var CommandAsService */
	private $injectorCommandAsService;

	/** @var CommandInCommands @inject */
	public $annotationCommandInCommands;

	/** @var TestService @inject */
	public $annotationTestService;

	/** @var CommandAsService @inject */
	public $annotationCommandAsService;

	/**
	 * @param CommandInCommands $commandInCommands
	 * @param TestService $testService
	 * @param CommandAsService $commandAsService
	 */
	public function __construct(
		CommandInCommands $commandInCommands,
		TestService $testService,
		CommandAsService $commandAsService
	)
	{
		$this->constructorCommandInCommands = $commandInCommands;
		$this->constructorTestService = $testService;
		$this->constructorCommandAsService = $commandAsService;
	}

	/**
	 * @param CommandInCommands $injectorCommandInCommands
	 */
	public function injectInjectorCommandInCommands(CommandInCommands $injectorCommandInCommands)
	{
		$this->injectorCommandInCommands = $injectorCommandInCommands;
	}

	/**
	 * @param TestService $injectorTestService
	 */
	public function injectInjectorTestService(TestService $injectorTestService)
	{
		$this->injectorTestService = $injectorTestService;
	}

	/**
	 * @param CommandAsService $injectorCommandAsService
	 */
	public function injectInjectorCommandAsService(CommandAsService $injectorCommandAsService)
	{
		$this->injectorCommandAsService = $injectorCommandAsService;
	}

	/**
	 * @throws \Tester\AssertException if constructor injects fail (NOT nonsense, since this is a trait -- constructor is overriden)
	 */
	public function validateConstructor()
	{
		Assert::type('KdybyTests\Console\DI\CommandInCommands', $this->constructorCommandInCommands, 'Constructor fail');
		Assert::type('KdybyTests\Console\DI\TestService', $this->constructorTestService, 'Constructor fail');
		Assert::type('KdybyTests\Console\DI\CommandAsService', $this->constructorCommandAsService, 'Constructor fail');
	}

	/**
	 * @throws \Tester\AssertException if annotation injects failed
	 */
	public function validateAnnotations()
	{
		Assert::type('KdybyTests\Console\DI\CommandInCommands', $this->annotationCommandInCommands, 'Annotation fail');
		Assert::type('KdybyTests\Console\DI\TestService', $this->annotationTestService, 'Annotation fail');
		Assert::type('KdybyTests\Console\DI\CommandAsService', $this->annotationCommandAsService, 'Annotation fail');
	}

	/**
	 * @throws \Tester\AssertException if annotation not injects failed
	 */
	public function validateNotAnnotations()
	{
		Assert::same(null, $this->annotationCommandInCommands, 'Annotation fail');
		Assert::same(null, $this->annotationTestService, 'Annotation fail');
		Assert::same(null, $this->annotationCommandAsService, 'Annotation fail');
	}

	/**
	 * @throws \Tester\AssertException if injector injects failed
	 */
	public function validateInjector()
	{
		Assert::type('KdybyTests\Console\DI\CommandInCommands', $this->injectorCommandInCommands, 'Injector fail');
		Assert::type('KdybyTests\Console\DI\TestService', $this->injectorTestService, 'Injector fail');
		Assert::type('KdybyTests\Console\DI\CommandAsService', $this->injectorCommandAsService, 'Injector fail');
	}

	/**
	 * @throws \Tester\AssertException if injector injects did not fail
	 */
	public function validateNotInjector()
	{
		Assert::same(null, $this->injectorCommandInCommands, 'Injector fail');
		Assert::same(null, $this->injectorTestService, 'Injector fail');
		Assert::same(null, $this->injectorCommandAsService, 'Injector fail');
	}

}

/**
 * Basic command defined in commands section, without any dependencies
 * @package KdybyTests\Console\DI
 */
class CommandInCommands extends DiTestCommand
{
	const COMMAND_NAME = 'app:commands:command';
	const RETURN_CODE = 20;
}

/**
 * Basic command defined in services, without any dependencies
 * @package KdybyTests\Console\DI
 */
class CommandAsService extends DiTestCommand
{
	const COMMAND_NAME = 'app:services:command';
	const RETURN_CODE = 30;
}

/**
 * Command defined in commands section, that needs all dependencies
 * @package KdybyTests\Console\DI
 */
class CommandInCommandsNeedsAll extends DiTestDependantCommand
{
	const COMMAND_NAME = 'app:commands:needsAll';
	const RETURN_CODE = 110;
}

/**
 * Command defined in services section, that needs all dependencies
 * @package KdybyTests\Console\DI
 */
class CommandAsServiceNeedsAll extends DiTestDependantCommand
{
	const COMMAND_NAME = 'app:services:needsAll';
	const RETURN_CODE = 120;
}

/**
 * Command defined in services section, that needs all dependencies
 * @package KdybyTests\Console\DI
 */
class CommandInCommandsNeedsAllWithInject extends DiTestDependantCommand
{
	const COMMAND_NAME = 'app:commands:needsAllWithInject';
	const RETURN_CODE = 130;
}

/**
 * Command defined in services section, that needs all dependencies
 * @package KdybyTests\Console\DI
 */
class CommandAsServiceNeedsAllWithInject extends DiTestDependantCommand
{
	const COMMAND_NAME = 'app:services:needsAllWithInject';
	const RETURN_CODE = 140;
}

/**
 * Basic test service defined in services, without any dependencies
 * @package KdybyTests\Console\DI
 */
class TestService
{
}

/**
 * Helper defined in console.helpers section
 * @package KdybyTests\Console\DI
 */
class HelperInSection extends Helper
{
	public function getName()
	{
		return 'HelperInSection';
	}
}

/**
 * Helper defined in services section
 * @package KdybyTests\Console\DI
 */
class HelperAsService extends Helper
{
	public function getName()
	{
		return 'HelperAsService';
	}
}

\run(new DiWiringTest());
