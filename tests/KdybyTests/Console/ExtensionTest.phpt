<?php

declare(strict_types = 1);

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

	public function createConfigurator(): Configurator
	{
		$config = new Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters([
			'container' => [
				'class' => 'SystemContainer_' . \md5((string) \mt_rand()),
			],
		]);
		$config->onCompile[] = static function ($config, \Nette\DI\Compiler $compiler): void {
			$compiler->addExtension('console', new \Kdyby\Console\DI\ConsoleExtension());
		};
		$config->addConfig(__DIR__ . '/config/allow.neon');
		$config->addConfig(__DIR__ . '/../nette-reset.neon');

		return $config;
	}

	public function testFunctionality(): void
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

	public function testShortUrl(): void
	{
		$this->invokeTestOnConfig(__DIR__ . '/config/short-url.neon');
	}

	public function testUrlWithoutTld(): void
	{
		$this->invokeTestOnConfig(__DIR__ . '/config/url-without-tld.neon');
	}

	private function invokeTestOnConfig(string $file): void
	{
		$config = $this->createConfigurator();
		$config->addConfig($file);
		Assert::true($config->createContainer() instanceof DIContainer);
	}

}

(new ExtensionTest())->run();
