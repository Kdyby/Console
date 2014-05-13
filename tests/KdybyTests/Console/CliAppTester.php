<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace KdybyTests\Console;

use Kdyby;
use Nette;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;



/**
 * Eases the testing of console applications.
 *
 * When testing an application, don't forget to disable the auto exit flag:
 *
 *     $application = new Application();
 *     $application->setAutoExit(false);
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Filip Procházka <filip@prochazka.su>
 */
class CliAppTester
{

	/**
	 * @var \Symfony\Component\Console\Application
	 */
	private $application;

	/**
	 * @var ArgvInput
	 */
	private $input;

	/**
	 * @var StreamOutput
	 */
	private $output;

	/**
	 * @var int
	 */
	private $statusCode;



	/**
	 * Constructor.
	 *
	 * @param Application $application An Application instance to test.
	 */
	public function __construct(Application $application)
	{
		$this->application = $application;
	}



	/**
	 * Executes the application.
	 *
	 * Available options:
	 *
	 *  * interactive: Sets the input interactive flag
	 *  * decorated:   Sets the output decorated flag
	 *  * verbosity:   Sets the output verbosity flag
	 *
	 * @param array $input An array of arguments and options
	 * @param array $options An array of options
	 *
	 * @return int     The command exit code
	 */
	public function run(array $input, $options = array())
	{
		$this->input = new ArgvInput($input);
		if (isset($options['interactive'])) {
			$this->input->setInteractive($options['interactive']);
		}

		$this->output = new StreamOutput(fopen('php://memory', 'w', false));
		if (isset($options['decorated'])) {
			$this->output->setDecorated($options['decorated']);
		}
		if (isset($options['verbosity'])) {
			$this->output->setVerbosity($options['verbosity']);
		}

		return $this->statusCode = $this->application->run($this->input, $this->output);
	}



	/**
	 * Gets the display returned by the last execution of the application.
	 *
	 * @param bool $normalize Whether to normalize end of lines to \n or not
	 *
	 * @return string The display
	 */
	public function getDisplay($normalize = false)
	{
		rewind($this->output->getStream());

		$display = stream_get_contents($this->output->getStream());

		if ($normalize) {
			$display = str_replace(PHP_EOL, "\n", $display);
		}

		return $display;
	}



	/**
	 * Gets the input instance used by the last execution of the application.
	 *
	 * @return InputInterface The current input instance
	 */
	public function getInput()
	{
		return $this->input;
	}



	/**
	 * Gets the output instance used by the last execution of the application.
	 *
	 * @return OutputInterface The current output instance
	 */
	public function getOutput()
	{
		return $this->output;
	}



	/**
	 * Gets the status code returned by the last execution of the application.
	 *
	 * @return int     The status code
	 */
	public function getStatusCode()
	{
		return $this->statusCode;
	}

}
