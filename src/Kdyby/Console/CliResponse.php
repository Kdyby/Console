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



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class CliResponse extends Nette\Object implements Nette\Application\IResponse
{

	/**
	 * @var int
	 */
	private $exitCode;



	/**
	 * @param int $exitCode
	 */
	public function __construct($exitCode)
	{
		$this->exitCode = $exitCode;
	}



	/**
	 * @return int
	 */
	public function getExitCode()
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
	public function send(Nette\Http\IRequest $httpRequest, Nette\Http\IResponse $httpResponse)
	{
		exit($this->exitCode);
	}

}
