<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Console;

use ReflectionProperty;

/**
 * @see https://github.com/symfony/debug/blob/2a237220d7d24a4ac867f701fe07320f341b2454/Exception/FatalThrowableError.php
 */
class FatalThrowableError extends \ErrorException
{

	/** @var \Throwable */
	private $cause;

	public function __construct(\Throwable $e)
	{
		if ($e instanceof \ParseError) {
			$message = 'Parse error: ' . $e->getMessage();
			$severity = E_PARSE;
		} elseif ($e instanceof \TypeError) {
			$message = 'Type error: ' . $e->getMessage();
			$severity = E_RECOVERABLE_ERROR;
		} else {
			$message = $e->getMessage();
			$severity = E_ERROR;
		}

		parent::__construct(
			$message,
			$e->getCode(),
			$severity,
			$e->getFile(),
			$e->getLine()
		);

		$this->cause = $e;
		$this->setTrace($e->getTrace());
	}

	/**
	 * @return \Throwable
	 */
	public function getCause()
	{
		return $this->cause;
	}

	private function setTrace($trace)
	{
		$traceReflector = new ReflectionProperty(\Exception::class, 'trace');
		$traceReflector->setAccessible(TRUE);
		$traceReflector->setValue($this, $trace);
	}

}
