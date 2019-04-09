<?php

/**
 * Test: Kdyby\Console\Extension.
 *
 * @testCase
 */

namespace KdybyTests\Console;

use Kdyby\Console\Application;
use Nette\Configurator;
use Nette\DI\Container as DIContainer;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

class ExtensionTest extends \Tester\TestCase
{

	public function createConfigurator()
	{
		$config = new Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters([
			'container' => [
				'class' => 'SystemContainer_' . \md5((string) \mt_rand()),
			],
        ]);
		$config->onCompile[] = static function ($config, \Nette\DI\Compiler $compiler) : void {
			$compiler->addExtension('console', new \Kdyby\Console\DI\ConsoleExtension());
		};
		$config->addConfig(__DIR__ . '/config/allow.neon');
		$config->addConfig(__DIR__ . '/../nette-reset.neon');

		return $config;
	}

	public function testFunctionality()
	{
		$config = $this->createConfigurator();
		$config->addConfig(__DIR__ . '/config/commands.neon');
		$container = $config->createContainer();
		/** @var \Nette\DI\Container $container */

		$app = $container->getService('console.application');
		/** @var \Kdyby\Console\Application $app */
		Assert::true($app instanceof Application);
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

	/**
	 * @param string $file
	 */
	private function invokeTestOnConfig($file)
	{
		$config = $this->createConfigurator();
		$config->addConfig($file);
		Assert::true($config->createContainer() instanceof DIContainer);
	}

}

(new ExtensionTest())->run();
