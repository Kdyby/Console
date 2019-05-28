<?php

declare(strict_types = 1);

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Console\Helpers;

use Nette\Application\Application;

class PresenterHelper extends \Symfony\Component\Console\Helper\Helper
{

	use \Kdyby\StrictObjects\Scream;

	/**
	 * @var \Nette\Application\Application
	 */
	private $app;

	public function __construct(Application $application)
	{
		$this->app = $application;
	}

	/**
	 * @return \Nette\Application\IPresenter|\Nette\Application\UI\Presenter
	 * @throws \Kdyby\Console\Exception\InvalidStateException
	 */
	public function getPresenter(): \Nette\Application\IPresenter
	{
		$presenter = $this->app->getPresenter();
		if ($presenter === NULL) {
			throw new \Kdyby\Console\Exception\InvalidStateException('There is currently no presenter');
		}

		return $presenter;
	}

	/**
	 * Returns the canonical name of this helper.
	 */
	public function getName(): string
	{
		return 'presenter';
	}

}
