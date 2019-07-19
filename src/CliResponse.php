<?php

declare(strict_types = 1);

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Console;

use Nette\Application\Application as NetteApplication;
use Nette\Http\IRequest;
use Nette\Http\IResponse;

class CliResponse implements \Nette\Application\IResponse
{

	use \Kdyby\StrictObjects\Scream;

	/**
	 * @var int
	 */
	private $exitCode;

	/**
	 * @var \Nette\Application\Application|NULL
	 */
	private $application;

	public function __construct(int $exitCode)
	{
		$this->exitCode = $exitCode;
	}

	/**
	 * @internal
	 */
	public function injectApplication(NetteApplication $application): void
	{
		$this->application = $application;
	}

	public function getExitCode(): int
	{
		return $this->exitCode;
	}

	/**
	 * Sends response to output.
	 *
	 * @param \Nette\Http\IRequest $httpRequest
	 * @param \Nette\Http\IResponse $httpResponse
	 * @return void
	 */
	public function send(IRequest $httpRequest, IResponse $httpResponse): void
	{
		if ($this->application !== NULL && $this->application->onShutdown !== NULL) {
			foreach ($this->application->onShutdown as $handler) {
				$handler($this->application);
			}
		}

		exit($this->exitCode);
	}

}
