<?php

/**
 * Test: Kdyby\Console\Extension.
 *
 * @testCase Kdyby\Console\ExtensionTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Console
 */

namespace KdybyTests\Console;

use Kdyby;
use Nette;
use Symfony\Component\Console\Command\Command;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ExtensionTest extends Tester\TestCase
{

	public function testFunctionality()
	{
		$config = new Nette\Config\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		Kdyby\Console\DI\ConsoleExtension::register($config);
		$config->addConfig(__DIR__ . '/config/commands.neon', FALSE);
		$container = $config->createContainer();
		/** @var \Nette\DI\Container|\SystemContainer $container */

		$app = $container->getService('console.application');
		/** @var Kdyby\Console\Application $app */
		Assert::true($app instanceof Kdyby\Console\Application);
		Assert::equal(1, count($app->all('test')));
	}

}



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class CommandMock extends Command
{

	protected function configure()
	{
		$this->setName('test:mock')->setDescription('Just a mock');
	}

}

\run(new ExtensionTest());
