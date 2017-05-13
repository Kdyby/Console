<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace KdybyModule;

use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class CliPresenter extends Nette\Application\UI\Presenter
{

	/**
	 * @var Kdyby\Console\Application
	 */
	private $console;

	/**
	 * @var Nette\Application\Application
	 */
	private $application;



	protected function startup()
	{
		parent::startup();
		$this->autoCanonicalize = FALSE;
	}



	/**
	 * @param Kdyby\Console\Application $console
	 */
	public function injectConsole(
		Kdyby\Console\Application $console,
		Nette\Application\Application $application
	)
	{
		$this->console = $console;
		$this->application = $application;
	}



	public function actionDefault()
	{
		$params = $this->request->getParameters();
		Nette\Utils\Validators::assertField($params, 'input', 'Symfony\Component\Console\Input\Input');
		Nette\Utils\Validators::assertField($params, 'output', 'Symfony\Component\Console\Output\OutputInterface');
		$response = new Kdyby\Console\CliResponse($this->console->run($params['input'], $params['output']));
		$response->injectApplication($this->application);
		$this->sendResponse($response);
	}

}
