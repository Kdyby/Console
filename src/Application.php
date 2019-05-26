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

use Nette\Application\Application as NetteApplication;
use Nette\DI\Container;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Tracy\Debugger;
use Tracy\Dumper;

class Application extends \Symfony\Component\Console\Application
{

	use \Kdyby\StrictObjects\Scream;

	public const CLI_SAPI = 'cli';
	public const INPUT_ERROR_EXIT_CODE = 253;
	public const INVALID_APP_MODE_EXIT_CODE = 252;

	/**
	 * @var string[]
	 */
	private static $invalidArgumentExceptions = [
		\RuntimeException::class,
		\InvalidArgumentException::class,
		\Symfony\Component\Console\Exception\RuntimeException::class,
	];

	/**
	 * @var \Nette\DI\Container
	 */
	private $serviceLocator;

	public function __construct(string $name = 'Nette Framework', string $version = 'UNKNOWN')
	{
		if ( ! $version && \class_exists(\Nette\DI\Definitions\ServiceDefinition::class)) {
			$version = \Kdyby\Console\DI\ConsoleExtension::NETTE_VERSION_30;

		} elseif ( ! $version && \class_exists(\Nette\DI\ServiceDefinition::class)) {
			$version = \Kdyby\Console\DI\ConsoleExtension::NETTE_VERSION_24;
		}

		parent::__construct($name, $version);

		$this->setCatchExceptions(FALSE);
		$this->setAutoExit(FALSE);
	}

	public function injectServiceLocator(Container $sl): void
	{
		$this->serviceLocator = $sl;
	}

	/**
	 * @param string $name
	 */
	public function find($name): Command
	{
		try {
			return parent::find($name);

		} catch (\InvalidArgumentException $e) {
			throw new \Kdyby\Console\Exception\UnknownCommandException($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * @param \Symfony\Component\Console\Input\InputInterface $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 * @return int
	 * @throws \Exception
	 */
	public function run(?InputInterface $input = NULL, ?OutputInterface $output = NULL): int
	{
		$input = $input ?: new ArgvInput();
		$output = $output ?: new ConsoleOutput();

		if ($input->hasParameterOption('--debug-mode')) {
			if ($input->hasParameterOption(['--debug-mode=no', '--debug-mode=off', '--debug-mode=false', '--debug-mode=0'])) {
				if ($this->serviceLocator->parameters['debugMode']) {
					$this->renderException(new \Kdyby\Console\Exception\InvalidApplicationModeException(
						'The app is running in debug mode. You have to use Kdyby\Console\DI\BootstrapHelper in app/bootstrap.php, ' .
						'Kdyby\Console cannot switch already running app to production mode.'
					), $output);

					return self::INVALID_APP_MODE_EXIT_CODE;
				}

			} else {
				if (!$this->serviceLocator->parameters['debugMode']) {
					$this->renderException(new \Kdyby\Console\Exception\InvalidApplicationModeException(
						'The app is running in production mode. You have to use Kdyby\Console\DI\BootstrapHelper in app/bootstrap.php, ' .
						'Kdyby\Console cannot switch already running app to debug mode.'
					), $output);

					return self::INVALID_APP_MODE_EXIT_CODE;
				}
			}
		}

		if (class_exists(Dumper::class) && $input->hasParameterOption('--no-ansi')) {
			Dumper::$terminalColors = FALSE;
		}

		try {
			return parent::run($input, $output);

		} catch (\Kdyby\Console\Exception\UnknownCommandException $e) {
			$this->renderException($e->getPrevious(), $output);
			[$message] = explode("\n", $e->getMessage());
			Debugger::log($message, Debugger::ERROR);

			return self::INPUT_ERROR_EXIT_CODE;

		} catch (\Exception $e) {
			if (in_array(get_class($e), self::$invalidArgumentExceptions, TRUE)
				&& preg_match('/^(The "-?-?.+" (option|argument) (does not (exist|accept a value)|requires a value)|(Not enough|Too many) arguments.*)\.$/', $e->getMessage()) === 1
			) {
				$this->renderException($e, $output);
				Debugger::log($e->getMessage(), Debugger::ERROR);
				return self::INPUT_ERROR_EXIT_CODE;
			}

		} catch (\Throwable $e) {
			$e = new FatalThrowableError($e);
		}

		$app = $this->serviceLocator->getByType(NetteApplication::class, FALSE);
		if ($app !== NULL) {
			/** @var \Nette\Application\Application $app */
			$app->onError($app, $e);
		}

		$this->handleException($e, $output);

		return max(min((int) $e->getCode(), 254), 1);
	}

	/**
	 * @param \Exception|\Throwable $e
	 * @param \Symfony\Component\Console\Output\OutputInterface|NULL $output
	 */
	public function handleException($e, ?OutputInterface $output = NULL): void
	{
		$output = $output ?: new ConsoleOutput();
		if ($e instanceof \Exception) {
			$this->renderException($e, $output);
		} else {
			$output->writeln(sprintf('<error>  %s  </error>', get_class($e)));
			$output->writeln(sprintf('<error>  %s  </error>', $e->getMessage()));
		}

		$file = Debugger::log($e, Debugger::ERROR);
		if ($file !== NULL) {
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

	protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output): int
	{
		if ($this->serviceLocator) {
			$this->serviceLocator->callInjects($command);
		}

		return parent::doRunCommand($command, $input, $output);
	}

	protected function getDefaultInputDefinition(): InputDefinition
	{
		$definition = parent::getDefaultInputDefinition();
		$definition->addOption(new InputOption('--debug-mode', NULL, InputOption::VALUE_OPTIONAL, 'Run the application in debug mode?'));

		return $definition;
	}

}
