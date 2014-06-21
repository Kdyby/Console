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

	private function prepareConfigurator()
	{
		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(array('container' => array('class' => 'SystemContainer_' . Nette\Utils\Strings::random())));
		Kdyby\Console\DI\ConsoleExtension::register($config);
		$config->addConfig(__DIR__ . '/config/allow.neon', $config::NONE);

		return $config;
	}



	public function testFunctionality()
	{
		$config = $this->prepareConfigurator();
		$config->addConfig(__DIR__ . '/config/commands.neon', $config::NONE);
		$container = $config->createContainer();
		/** @var \Nette\DI\Container|\SystemContainer $container */

		$app = $container->getService('console.application');
		/** @var Kdyby\Console\Application $app */
		Assert::true($app instanceof Kdyby\Console\Application);
		Assert::equal(1, count($app->all('test')));
	}



	public function testShortUrl()
	{
		$this->invokeTestOnConfig(__DIR__ . '/config/short-url.neon');
	}



	public function testUrlWithoutTld()
	{
		$this->invokeTestOnConfig(__DIR__ . '/config/url-without-tld.neon');
	}



	private function invokeTestOnConfig($file)
	{
		$config = $this->prepareConfigurator();
		$config->addConfig($file, $config::NONE);
		Assert::true($config->createContainer() instanceof Nette\DI\Container);
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
