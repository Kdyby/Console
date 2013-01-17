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
use Symfony\Component\Console\Output\Output;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class StringOutput extends Output
{

	/**
	 * @var string
	 */
	private $output;



	/**
	 * @param string $message A message to write to the output
	 * @param boolean $newline Whether to add a newline or not
	 */
	protected function doWrite($message, $newline)
	{
		$this->output .= $message . ($newline ? "\n" : '');
	}



	/**
	 * @return string
	 */
	public function getOutput()
	{
		return $this->output;
	}

}
