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
use Nette\Diagnostics\Debugger;
use Symfony;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Application extends Symfony\Component\Console\Application
{

	/**
	 * @param string $name
	 * @param string $version
	 */
	public function __construct($name = Nette\Framework::NAME, $version = Nette\Framework::VERSION)
	{
		parent::__construct($name, $version);

		$this->setCatchExceptions(FALSE);
		$this->setAutoExit(FALSE);
	}



	/**
	 * @param \Symfony\Component\Console\Input\InputInterface $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 * @return int
	 * @throws \Exception
	 */
	public function run(InputInterface $input = NULL, OutputInterface $output = NULL)
	{
		try {
			return parent::run($input, $output);

		} catch (\Exception $e) {
			if ($output instanceof ConsoleOutputInterface) {
				$this->renderException($e, $output->getErrorOutput());

			} else {
				$this->renderException($e, $output);
			}

			if ($file = Debugger::log($e, Debugger::ERROR)) {
				$output->writeln(sprintf('<error>  (Tracy output was stored in %s)  </error>', basename($file)));
				$output->writeln('');

				if (Debugger::$browser) {
					exec(Debugger::$browser . ' ' . escapeshellarg($file));
				}
			}

			return min((int) $e->getCode(), 255);
		}
	}

}
