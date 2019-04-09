<?php declare(strict_types=1);

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace KdybyModule;

use Kdyby\Console\Application as ConsoleApplication;
use Kdyby\Console\CliResponse;
use Nette\Application\Application as NetteApplication;
use Nette\Utils\Validators;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @phpcsSuppress KdybyCodingStandard.Files.TypeNameMatchesFileName
 */
class CliPresenter extends \Nette\Application\UI\Presenter
{

	const NAME = 'Kdyby:Cli';

	/**
	 * @var \Kdyby\Console\Application|NULL
	 */
	private $console;

	/**
	 * @var \Nette\Application\Application|NULL
	 */
	private $application;

	protected function startup()
	{
		parent::startup();
		$this->autoCanonicalize = FALSE;
	}

	/**
	 * @param \Kdyby\Console\Application $console
	 * @param \Nette\Application\Application $application
	 */
	public function injectConsole(
		ConsoleApplication $console,
		NetteApplication $application
	)
	{
		$this->console = $console;
		$this->application = $application;
	}

	public function actionDefault(): void
	{
		if ($this->console === NULL || $this->application === NULL) {
			throw new \Kdyby\Console\InvalidStateException('Before running the presenter, call injectConsole() with required dependencies.');
		}

		$request = $this->getRequest();
		if ($request === NULL) {
			throw new \Kdyby\Console\InvalidStateException(sprintf('Do not call %s directly, use %s::run()', __FUNCTION__, __CLASS__));
		}

		$params = $request->getParameters();
		Validators::assertField($params, 'input', InputInterface::class);
		Validators::assertField($params, 'output', OutputInterface::class);
		$response = new CliResponse($this->console->run($params['input'], $params['output']));
		$response->injectApplication($this->application);
		$this->sendResponse($response);
	}

}
