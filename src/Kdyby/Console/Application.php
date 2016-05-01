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
use Symfony;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tracy\Debugger;
use Tracy\Dumper;



/**
 * @author Filip Procházka <filip@prochazka.su>
 * @author Michal Gebauer <mishak@mishak.net>
 */
class Application extends Symfony\Component\Console\Application
{

	const INPUT_ERROR_EXIT_CODE = 253;
	const INVALID_APP_MODE_EXIT_CODE = 252;

	private static $invalidArgumentExceptions = [
		'RuntimeException',
		'InvalidArgumentException',
		'Symfony\Component\Console\Exception\RuntimeException',
	];

	/**
	 * @var Nette\DI\Container
	 */
	private $serviceLocator;



	/**
	 * @param string $name
	 * @param string $version
	 */
	public function __construct($name = 'Nette Framework', $version = NULL)
	{
		parent::__construct($name, $version ?: (class_exists('Nette\Framework') ? Nette\Framework::VERSION : 'UNKNOWN'));

		$this->setCatchExceptions(FALSE);
		$this->setAutoExit(FALSE);
	}



	public function injectServiceLocator(Nette\DI\Container $sl)
	{
		$this->serviceLocator = $sl;
	}



	public function find($name)
	{
		try {
			return parent::find($name);

		} catch (\InvalidArgumentException $e) {
			throw new UnknownCommandException($e->getMessage(), $e->getCode(), $e);
		}
	}



	/**
	 * @param \Symfony\Component\Console\Input\InputInterface $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 * @return int
	 * @throws \Exception
	 */
	public function run(InputInterface $input = NULL, OutputInterface $output = NULL)
	{
		$input = $input ?: new ArgvInput();
		$output = $output ?: new ConsoleOutput();

		if ($input->hasParameterOption('--debug-mode')) {
			if ($input->hasParameterOption(['--debug-mode=no', '--debug-mode=off', '--debug-mode=false', '--debug-mode=0'])) {
				if ($this->serviceLocator->parameters['debugMode']) {
					$this->renderException(new InvalidApplicationModeException(
						"The app is running in debug mode. You have to use Kdyby\\Console\\DI\\BootstrapHelper in app/bootstrap.php, " .
						"Kdyby\\Console cannot switch already running app to production mode . "
					), $output);

					return self::INVALID_APP_MODE_EXIT_CODE;
				}

			} else {
				if (!$this->serviceLocator->parameters['debugMode']) {
					$this->renderException(new InvalidApplicationModeException(
						"The app is running in production mode. You have to use Kdyby\\Console\\DI\\BootstrapHelper in app/bootstrap.php, " .
						"Kdyby\\Console cannot switch already running app to debug mode."
					), $output);

					return self::INVALID_APP_MODE_EXIT_CODE;
				}
			}
		}

		if (class_exists('Tracy\Dumper') && $input->hasParameterOption('--no-ansi')) {
			Dumper::$terminalColors = FALSE;
		}

		try {
			return parent::run($input, $output);

		} catch (UnknownCommandException $e) {
			$this->renderException($e->getPrevious(), $output);
			list($message) = explode("\n", $e->getMessage());
			Debugger::log($message, Debugger::ERROR);

			return self::INPUT_ERROR_EXIT_CODE;

		} catch (\Exception $e) {
			if (in_array(get_class($e), self::$invalidArgumentExceptions, TRUE)
				&& preg_match('/^(The "-?-?.+" (option|argument) (does not (exist|accept a value)|requires a value)|(Not enough|Too many) arguments.*)\.$/', $e->getMessage()) === 1
			) {
				$this->renderException($e, $output);
				Debugger::log($e->getMessage(), Debugger::ERROR);
				return self::INPUT_ERROR_EXIT_CODE;

			} elseif ($app = $this->serviceLocator->getByType('Nette\Application\Application', FALSE)) {
				/** @var Nette\Application\Application $app */
				$app->onError($app, $e);

			} else {
				$this->handleException($e, $output);
			}

			return max(min((int) $e->getCode(), 254), 1);
		}
	}



	/**
	 * @param \Exception|\Throwable $e
	 * @param OutputInterface|NULL $output
	 */
	public function handleException($e, OutputInterface $output = NULL)
	{
		$output = $output ? : new ConsoleOutput();
		$this->renderException($e, $output);

		if ($file = Debugger::log($e, Debugger::ERROR)) {
			$output->writeln(sprintf('<error>  (Tracy output was stored in %s)  </error>', basename($file)));
			$output->writeln('');

			if (Debugger::$browser) {
				if (!file_exists($file)) {
					$file = Debugger::$logDirectory . '/' . $file;
				}

				exec(Debugger::$browser . ' ' . escapeshellarg($file));
			}
		}
	}



	protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output)
	{
		if ($this->serviceLocator) {
			$this->serviceLocator->callInjects($command);
		}

		return parent::doRunCommand($command, $input, $output);
	}



	protected function getDefaultInputDefinition()
	{
		$definition = parent::getDefaultInputDefinition();
		$definition->addOption(new InputOption('--debug-mode', NULL, InputOption::VALUE_OPTIONAL, 'Run the application in debug mode?'));

		return $definition;
	}

}
