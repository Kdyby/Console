<?php

/**
 * Test: Kdyby\Console\CliRouter.
 *
 * @testCase
 */

namespace KdybyTests\Console;

use Kdyby\Console\Application;
use Kdyby\Console\CliResponse;
use Kdyby\Console\CliRouter;
use Kdyby\Console\DI\ConsoleExtension;
use Kdyby\Console\StringOutput;
use KdybyModule\CliPresenter;
use Nette\Application\Request;
use Nette\Application\Routers\RouteList;
use Nette\Application\UI\Presenter;
use Nette\Configurator;
use Nette\Http\Request as HttpRequest;
use Nette\Http\UrlScript;
use Nette\Routing\Router;
use Symfony\Component\Console\Input\StringInput;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

class CliRouterTest extends \Tester\TestCase
{

	public function testFunctionality()
	{
		$config = new Configurator();
		$config->setTempDirectory(TEMP_DIR);
		ConsoleExtension::register($config);
		$config->addConfig(__DIR__ . '/config/short-url.neon');
		$config->addConfig(__DIR__ . '/config/allow.neon');
		$config->addConfig(__DIR__ . '/../nette-reset.neon');
		$container = $config->createContainer();
		/** @var \Nette\DI\Container $container */

		$router = $container->getByType(Router::class);
		/** @var \Nette\Application\Routers\RouteList $router */
		Assert::true($router instanceof RouteList);

		list($cliRouter) = iterator_to_array($router->getIterator());
		/** @var \Kdyby\Console\CliRouter $cliRouter */
		Assert::true($cliRouter instanceof CliRouter);

		$cliRouter->setInput(new StringInput('list')); // lists default commands
		$cliRouter->setOutput($output = new StringOutput());
		$cliRouter->allowedMethods[] = 'cgi-fcgi'; // nette tester

		$appRequest = $router->match(new HttpRequest(new UrlScript()));
		Assert::type('array', $appRequest);
		Assert::same($appRequest['presenter'], CliPresenter::NAME);
		Assert::same($appRequest['method'], Application::CLI_SAPI);

		// create presenter
		$presenter = new CliPresenter();
		$container->callMethod([$presenter, 'injectPrimary']);
		$container->callMethod([$presenter, 'injectConsole']);

		$appRequest = new Request(
			$appRequest[Presenter::PRESENTER_KEY] ?? NULL,
			$appRequest['method'],
			$appRequest,
			[],
			[],
			[Request::SECURED => TRUE]
		);

		// run presenter
		$appResponse = $presenter->run($appRequest);
		/** @var \Kdyby\Console\CliResponse $appResponse */
		Assert::true($appResponse instanceof CliResponse);
		Assert::same(0, $appResponse->getExitCode());
		Assert::match('%A%Usage:%A%', $output->getOutput());
	}

}

(new CliRouterTest())->run();
