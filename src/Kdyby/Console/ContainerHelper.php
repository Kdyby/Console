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
use Symfony\Component\Console;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ContainerHelper extends Console\Helper\Helper
{

	/**
	 * @var \Nette\DI\Container
	 */
	private $container;



	/**
	 * @param \Nette\DI\Container $dic
	 */
	public function __construct(Nette\DI\Container $dic)
	{
		$this->container = $dic;
	}



	/**
	 * @return \Nette\DI\Container
	 */
	public function getContainer()
	{
		return $this->container;
	}



	/**
	 * @param string $type
	 * @return object
	 */
	public function getByType($type)
	{
		return $this->container->getByType($type);
	}



	/**
	 * Returns the canonical name of this helper.
	 *
	 * @return string The canonical name
	 *
	 * @api
	 */
	public function getName()
	{
		return 'container';
	}

}
