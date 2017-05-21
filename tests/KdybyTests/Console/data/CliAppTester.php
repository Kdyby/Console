<?php

namespace KdybyTests\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * Eases the testing of console applications.
 *
 * When testing an application, don't forget to disable the auto exit flag:
 *
 *     $application = new Application();
 *     $application->setAutoExit(false);
 */
class CliAppTester
{

	use \Kdyby\StrictObjects\Scream;

	/**
	 * @var \Symfony\Component\Console\Application
	 */
	private $application;

	/**
	 * @var \Symfony\Component\Console\Input\ArgvInput
	 */
	private $input;

	/**
	 * @var \Symfony\Component\Console\Output\StreamOutput
	 */
	private $output;

	/**
	 * @var int
	 */
	private $statusCode;

	/**
	 * @param \Symfony\Component\Console\Application $application
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
	 * @return int The command exit code
	 */
	public function run(array $input, $options = [])
	{
		$this->input = new ArgvInput($input);
		if (isset($options['interactive'])) {
			$this->input->setInteractive($options['interactive']);
		}

		$this->output = new StreamOutput(fopen('php://memory', 'w', FALSE));
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
	public function getDisplay($normalize = FALSE)
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
	 * @return \Symfony\Component\Console\Input\InputInterface The current input instance
	 */
	public function getInput()
	{
		return $this->input;
	}

	/**
	 * Gets the output instance used by the last execution of the application.
	 *
	 * @return \Symfony\Component\Console\Output\OutputInterface The current output instance
	 */
	public function getOutput()
	{
		return $this->output;
	}

	/**
	 * Gets the status code returned by the last execution of the application.
	 *
	 * @return int The status code
	 */
	public function getStatusCode()
	{
		return $this->statusCode;
	}

}
