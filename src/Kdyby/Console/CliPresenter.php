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
use Nette\Application;
use Nette\Http;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class CliPresenter extends Nette\Application\UI\Presenter
{

	/**
	 * @var Kdyby\Console\Application
	 */
	private $console;



	protected function startup()
	{
		parent::startup();
		$this->autoCanonicalize = FALSE;
	}



	/**
	 * @param Kdyby\Console\Application $console
	 */
	public function injectConsole(Kdyby\Console\Application $console)
	{
		$this->console = $console;
	}



	public function actionDefault()
	{
		$params = $this->request->getParameters();
		Nette\Utils\Validators::assertField($params, 'input', 'Symfony\Component\Console\Input\Input');
		Nette\Utils\Validators::assertField($params, 'output', 'Symfony\Component\Console\Output\OutputInterface');
		$this->sendResponse(new Kdyby\Console\CliResponse($this->console->run($params['input'], $params['output'])));
	}



	public function injectPrimary(Nette\DI\Container $context = NULL, Application\IPresenterFactory $presenterFactory = NULL, Application\IRouter $router = NULL,
		Http\IRequest $httpRequest, Http\IResponse $httpResponse, Http\Session $session = NULL, ITemplateFactory $templateFactory = NULL)
	{
		parent::injectPrimary($context, $presenterFactory, $router, $httpRequest, $httpResponse, $session, NULL, $templateFactory);
	}

}
