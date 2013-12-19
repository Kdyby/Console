<?php

/**
 * Test: Kdyby\Console\CliRouter.
 *
 * @testCase Kdyby\Console\CliRouterTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Console
 */

namespace KdybyTests\Console;

use Kdyby;
use KdybyModule;
use Nette;
use Symfony\Component\Console\Input\StringInput;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class CliRouterTest extends Tester\TestCase
{

	public function testFunctionality()
	{
		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		Kdyby\Console\DI\ConsoleExtension::register($config);
		$config->addConfig(__DIR__ . '/config/short-url.neon', $config::NONE);
		$config->addConfig(__DIR__ . '/config/allow.neon', $config::NONE);
		$container = $config->createContainer();
		/** @var Nette\DI\Container $container */

		$router = $container->getByType('Nette\Application\IRouter');
		/** @var Nette\Application\Routers\RouteList $router */
		Assert::true($router instanceof Nette\Application\Routers\RouteList);

		list($cliRouter) = iterator_to_array($router->getIterator());
		/** @var Kdyby\Console\CliRouter $cliRouter */
		Assert::true($cliRouter instanceof Kdyby\Console\CliRouter);

		$cliRouter->setInput(new StringInput('list')); // lists default commands
		$cliRouter->setOutput($output = new Kdyby\Console\StringOutput());
		$cliRouter->allowedMethods[] = 'cgi-fcgi'; // nette tester

		$appRequest = $router->match(new Nette\Http\Request(new Nette\Http\UrlScript()));
		Assert::true($appRequest instanceof Nette\Application\Request);
		Assert::same($appRequest->getPresenterName(), 'Kdyby:Cli');
		Assert::same($appRequest->getMethod(), 'cli');

		// create presenter
		$presenter = new KdybyModule\CliPresenter();
		$container->callMethod(array($presenter, 'injectPrimary'));
		$container->callMethod(array($presenter, 'injectConsole'));

		// run presenter
		$appResponse = $presenter->run($appRequest);
		/** @var Kdyby\Console\CliResponse $appResponse */
		Assert::true($appResponse instanceof Kdyby\Console\CliResponse);
		Assert::same(0, $appResponse->getExitCode());
		Assert::match("Nette Framework version %a%

Usage:
  [options] command [arguments]

Options:
  --help           -h Display this help message.
  --quiet          -q Do not output any message.
  --verbose        -v|vv|vvv Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
  --version        -V Display this application version.
  --ansi              Force ANSI output.
  --no-ansi           Disable ANSI output.
  --no-interaction -n Do not ask any interactive question.

Available commands:
  help   Displays help for a command
  list   Lists commands", $output->getOutput());
	}

}

\run(new CliRouterTest());
