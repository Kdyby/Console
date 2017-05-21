<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 */

namespace Kdyby\Console;

interface Exception
{

}

class InvalidArgumentException extends \InvalidArgumentException implements \Kdyby\Console\Exception
{

}

class InvalidApplicationModeException extends \LogicException implements \Kdyby\Console\Exception
{

}

class InvalidStateException extends \RuntimeException implements \Kdyby\Console\Exception
{

}

class UnknownCommandException extends \InvalidArgumentException implements \Kdyby\Console\Exception
{

}
