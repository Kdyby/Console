<?php

declare(strict_types = 1);

/**
 * Test: Kdyby\Console\CliRouter.
 *
 * @testCase
 */

namespace KdybyTests\Console;

use Kdyby\Console\Application;
use Kdyby\Console\CliResponse;
use Kdyby\Console\CliRouter;
use Kdyby\Console\StringOutput;
use KdybyModule\CliPresenter;
use Nette\Application\IRouter;
use Nette\Application\Routers\RouteList;
use Nette\Configurator;
use Nette\Http\Request as HttpRequest;
use Nette\Http\UrlScript;
use Symfony\Component\Console\Input\StringInput;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

class CliRouterTest extends \Tester\TestCase
{

	public function testFunctionality(): void
	{
		$config = new Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->onCompile[] = static function ($config, \Nette\DI\Compiler $compiler): void {
			$compiler->addExtension('console', new \Kdyby\Console\DI\ConsoleExtension());
		};
		$config->addConfig(__DIR__ . '/config/short-url.neon');
		$config->addConfig(__DIR__ . '/config/allow.neon');
		$config->addConfig(__DIR__ . '/../nette-reset.neon');
		$container = $config->createContainer();
		/** @var \Nette\DI\Container $container */

		$router = $container->getByType(IRouter::class);
		/** @var \Nette\Application\Routers\RouteList $router */
		Assert::true($router instanceof RouteList);

		[$cliRouter] = $router->getRouters();
		/** @var \Kdyby\Console\CliRouter $cliRouter */
		Assert::true($cliRouter instanceof CliRouter);

		$cliRouter->setInput(new StringInput('list')); // lists default commands
		$cliRouter->setOutput($output = new StringOutput());
		$cliRouter->allowedMethods[] = 'cgi-fcgi'; // nette tester

		$appRequest = $router->match(new HttpRequest(new UrlScript()));
		Assert::true($appRequest['input'] instanceof StringInput);
		Assert::true($appRequest['output'] instanceof StringOutput);
		Assert::same($appRequest['presenter'], CliPresenter::NAME);
		Assert::same($appRequest['method'], Application::CLI_SAPI);

		// create presenter
		$presenter = new CliPresenter();
		$container->callMethod([$presenter, 'injectPrimary']);
		$container->callMethod([$presenter, 'injectConsole']);

		// run presenter
		$appResponse = $presenter->run(
			new \Nette\Application\Request(
				$presenter->name,
				$appRequest['method'],
				$appRequest
			)
		);
		/** @var \Kdyby\Console\CliResponse $appResponse */
		Assert::true($appResponse instanceof CliResponse);
		Assert::same(0, $appResponse->getExitCode());
		Assert::match('%A%Usage:%A%', $output->getOutput());
	}

}

(new CliRouterTest())->run();
