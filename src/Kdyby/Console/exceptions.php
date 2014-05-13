<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 */

namespace Kdyby\Console;



/**
 * @author Filip Procházka <filip@prochazka.su>
 * @author Michal Gebauer <mishak@mishak.net>
 */
interface Exception
{

}



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class InvalidArgumentException extends \InvalidArgumentException implements Exception
{

}



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class InvalidStateException extends \RuntimeException implements Exception
{

}



/**
 * @author Michal Gebauer <mishak@mishak.net>
 */
class UnknownCommandException extends \InvalidArgumentException implements Exception
{

	public function __construct($message = '', \InvalidArgumentException $e)
	{
		parent::__construct($message, 253, $e);
	}

}
