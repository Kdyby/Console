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
use Nette;
use NetteModule;
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
		$config = new Nette\Config\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		Kdyby\Console\DI\ConsoleExtension::register($config);
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
		Assert::same($appRequest->getPresenterName(), 'Nette:Micro');
		Assert::same($appRequest->getMethod(), 'cli');

		$presenter = new NetteModule\MicroPresenter($container);
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
  --verbose        -v Increase verbosity of messages.
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
