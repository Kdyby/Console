<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Console;

use Nette\DI\Container as DIContainer;

class ContainerHelper extends \Symfony\Component\Console\Helper\Helper
{

	use \Kdyby\StrictObjects\Scream;

	/**
	 * @var \Nette\DI\Container
	 */
	private $container;

	/**
	 * @param \Nette\DI\Container $dic
	 */
	public function __construct(DIContainer $dic)
	{
		$this->container = $dic;
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function hasParameter($key)
	{
		return isset($this->container->parameters[$key]);
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function getParameter($key)
	{
		if (!$this->hasParameter($key)) {
			return NULL;
		}

		return $this->container->parameters[$key];
	}

	/**
	 * @return array
	 */
	public function getParameters()
	{
		return $this->container->parameters;
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
	 */
	public function getName()
	{
		return 'container';
	}

}
