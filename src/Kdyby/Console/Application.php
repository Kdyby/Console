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

}
