<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Console\Helpers;

use Kdyby;
use Nette;
use Symfony\Component\Console\Helper\Helper;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class PresenterHelper extends Helper
{

	/**
	 * @var \Nette\Application\Application
	 */
	private $app;



	/**
	 * @param \Nette\Application\Application $application
	 */
	public function __construct(Nette\Application\Application $application)
	{
		$this->app = $application;
	}



	/**
	 * @return \Nette\Application\IPresenter|\Nette\Application\UI\Presenter
	 * @throws \Kdyby\Console\InvalidStateException
	 */
	public function getPresenter()
	{
		if (!$presenter = $this->app->getPresenter()) {
			throw new Kdyby\Console\InvalidStateException("There is currently no presenter");
		}

		return $presenter;
	}



	/**
	 * Returns the canonical name of this helper.
	 *
	 * @return string The canonical name
	 */
	public function getName()
	{
		return 'presenter';
	}

}
