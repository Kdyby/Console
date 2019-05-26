<?php

declare(strict_types = 1);

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Console;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class CliRouter implements \Nette\Routing\Router
{

	use \Kdyby\StrictObjects\Scream;

	/**
	 * @var string[]
	 */
	public $allowedMethods = [Application::CLI_SAPI];

	/**
	 * @var \Symfony\Component\Console\Input\InputInterface|NULL
	 */
	private $input;

	/**
	 * @var \Symfony\Component\Console\Output\OutputInterface|NULL
	 */
	private $output;

	public function setOutput(OutputInterface $output): void
	{
		$this->output = $output;
	}

	public function setInput(InputInterface $input): void
	{
		$this->input = $input;
	}

	/**
	 * Maps HTTP request to a Request object.
	 *
	 * @return mixed[]
	 */
	public function match(\Nette\Http\IRequest $httpRequest): ?array
	{
		if (!in_array(PHP_SAPI, $this->allowedMethods, TRUE)) {
			return NULL;
		}

		if (empty($_SERVER['argv']) || !is_array($_SERVER['argv'])) {
			return NULL;
		}

		$input = $this->input;
		if ($input === NULL) {
			$input = new ArgvInput();
		}

		$output = $this->output;
		if ($output === NULL) {
			$output = new ConsoleOutput();
		}

		return [
			'action'    => 'default',
			'method'    => \Kdyby\Console\Application::CLI_SAPI,
			'presenter' => \KdybyModule\CliPresenter::NAME,
			'input'     => $input,
			'output'    => $output,
		];
	}

	/**
	 * Constructs absolute URL from Request object.
	 *
	 * @param mixed[] $params
	 */
	public function constructUrl(array $params, \Nette\Http\UrlScript $refUrl): ?string
	{
		return NULL;
	}

}
