<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Console;

use KdybyModule\CliPresenter;
use Nette\Application\Request as AppRequest;
use Nette\Http\IRequest;
use Nette\Http\Url;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class CliRouter implements \Nette\Application\IRouter
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

	/**
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 */
	public function setOutput(OutputInterface $output)
	{
		$this->output = $output;
	}

	/**
	 * @param \Symfony\Component\Console\Input\InputInterface $input
	 */
	public function setInput(InputInterface $input)
	{
		$this->input = $input;
	}

	/**
	 * Maps HTTP request to a Request object.
	 */
	public function match(IRequest $httpRequest)
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

		return new AppRequest(CliPresenter::NAME, Application::CLI_SAPI, [
			'action' => 'default',
			'input' => $input,
			'output' => $output,
		]);
	}

	/**
	 * Constructs absolute URL from Request object.
	 */
	public function constructUrl(AppRequest $appRequest, Url $refUrl)
	{
		return NULL;
	}

}
