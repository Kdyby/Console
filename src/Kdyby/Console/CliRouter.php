<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Console;

use Kdyby;
use Nette;
use Nette\Application\Request;
use Nette\Application\Routers\RouteList;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class CliRouter extends Nette\Object implements Nette\Application\IRouter
{

	/**
	 * @var array
	 */
	public $allowedMethods = array('cli');

	/**
	 * @var \Nette\DI\Container
	 */
	private $container;

	/**
	 * @var InputInterface
	 */
	private $input;

	/**
	 * @var OutputInterface
	 */
	private $output;



	/**
	 * @param \Nette\DI\Container $container
	 */
	public function __construct(Nette\DI\Container $container)
	{
		$this->container = $container;
	}



	/**
	 * @param OutputInterface $output
	 */
	public function setOutput(OutputInterface $output)
	{
		$this->output = $output;
	}



	/**
	 * @param InputInterface $input
	 */
	public function setInput(InputInterface $input)
	{
		$this->input = $input;
	}



	/**
	 * Maps HTTP request to a Request object.
	 */
	public function match(Nette\Http\IRequest $httpRequest)
	{
		if (!in_array(PHP_SAPI, $this->allowedMethods)) {
			return NULL;
		}

		if (($input = $this->input) === NULL) {
			$input = new ArgvInput();
		}

		if (($output = $this->output) === NULL) {
			$output = new ConsoleOutput();
		}

		return new Request('Kdyby:Cli', 'cli', array(
			'action' => 'default',
			'input' => $input,
			'output' => $output,
		));
	}



	/**
	 * Constructs absolute URL from Request object.
	 */
	public function constructUrl(Request $appRequest, Nette\Http\Url $refUrl)
	{
		return NULL;
	}



	/**
	 * @param \Nette\Application\IRouter $router
	 * @param CliRouter $cliRouter
	 * @throws \Nette\Utils\AssertionException
	 * @return \Nette\Application\Routers\RouteList
	 */
	public static function prependTo(Nette\Application\IRouter &$router, self $cliRouter)
	{
		if (!$router instanceof RouteList || $router->getModule()) {
			throw new Nette\Utils\AssertionException(
				'If you want to use Kdyby/Console then your main router ' .
				'must be an instance of Nette\Application\Routers\RouteList without module'
			);
		}

		$router[] = $cliRouter; // need to increase the array size

		$lastKey = count($router) - 1;
		foreach ($router as $i => $route) {
			if ($i === $lastKey) {
				break;
			}
			$router[$i + 1] = $route;
		}

		$router[0] = $cliRouter;
	}

}
