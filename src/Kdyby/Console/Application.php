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
use Symfony\Component\Console\Input\InputInterface;
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
		Nette\Diagnostics\Debugger::$productionMode = FALSE; // show in browser, bitch
		try {
			return parent::run($input, $output);

		} catch (\Exception $e) {
			Nette\Diagnostics\Debugger::log($e, 'cli');
			throw $e;
		}
	}

}
